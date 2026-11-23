import React, {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useRef,
  useState,
} from 'react';
import { StyleSheet, View } from 'react-native';
import { WebView } from 'react-native-webview';
import { API_URL } from '../api/client';
import { attendanceApi } from '../api/endpoints';
import { useAuth } from '../context/AuthContext';

export type FaceCaptureOutcome =
  | { status: 'captured'; photoData: string; faceScore: number }
  | { status: 'cancelled' }
  | { status: 'no_avatar' }
  | { status: 'error'; message: string };

type FaceCaptureContextValue = {
  startFaceCapture: (mode: 'in' | 'out') => Promise<FaceCaptureOutcome>;
};

const FaceCaptureContext = createContext<FaceCaptureContextValue | undefined>(undefined);

export const useFaceCapture = (): FaceCaptureContextValue => {
  const context = useContext(FaceCaptureContext);

  if (!context) {
    throw new Error('useFaceCapture must be used inside FaceCaptureProvider');
  }

  return context;
};

const appOrigin = (() => {
  try {
    return new URL(API_URL).origin;
  } catch (error) {
    return 'https://hr.rajasakti.co.id';
  }
})();

type CaptureMessage = {
  type: 'loaded' | 'ready' | 'captured' | 'cancel' | 'error';
  photo_data?: string;
  face_score?: number;
  message?: string;
  fatal?: boolean;
};

/**
 * Face engine persisten: halaman kamera dimuat sekali (mode warmup), model
 * pengenalan disiapkan di belakang, lalu WebView yang sama dipakai ulang untuk
 * check-in/check-out. Ini menghindari inisialisasi model di setiap absen.
 */
export const FaceCaptureProvider = ({ children }: { children: React.ReactNode }) => {
  const { user } = useAuth();
  const [url, setUrl] = useState<string | null>(null);
  const [active, setActive] = useState(false);
  const [noAvatar, setNoAvatar] = useState(false);
  const webviewRef = useRef<React.ElementRef<typeof WebView> | null>(null);
  const resolverRef = useRef<((outcome: FaceCaptureOutcome) => void) | null>(null);
  const pageLoadedRef = useRef(false);
  const pageLoadedWaiters = useRef<Array<() => void>>([]);
  const pendingModeRef = useRef<'in' | 'out' | null>(null);
  const startInjectedRef = useRef(false);
  const timeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  useEffect(() => {
    setUrl(null);
    setNoAvatar(false);
    pageLoadedRef.current = false;
    pageLoadedWaiters.current = [];

    if (!user?.id) {
      return;
    }

    let activeFetch = true;

    attendanceApi
      .faceCaptureUrl('in', true)
      .then((response) => {
        if (!activeFetch) return;

        if (!response.data.url) {
          if (response.data.reason === 'no_avatar') {
            setNoAvatar(true);
          }

          return;
        }

        setUrl(response.data.url);
      })
      .catch(() => {});

    return () => {
      activeFetch = false;
    };
  }, [user?.id, user?.avatar_url]);

  useEffect(
    () => () => {
      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current);
      }
    },
    [],
  );

  const finish = useCallback((outcome: FaceCaptureOutcome) => {
    const resolve = resolverRef.current;

    if (!resolve) {
      return;
    }

    resolverRef.current = null;
    pendingModeRef.current = null;
    startInjectedRef.current = false;

    if (timeoutRef.current) {
      clearTimeout(timeoutRef.current);
      timeoutRef.current = null;
    }

    setActive(false);
    webviewRef.current?.injectJavaScript('window.__faceCaptureStop && window.__faceCaptureStop(); true;');
    resolve(outcome);
  }, []);

  const waitForPage = useCallback(() => {
    if (pageLoadedRef.current) {
      return Promise.resolve();
    }

    return new Promise<void>((resolve) => {
      pageLoadedWaiters.current.push(resolve);
      setTimeout(resolve, 5000);
    });
  }, []);

  const startFaceCapture = useCallback(
    async (mode: 'in' | 'out'): Promise<FaceCaptureOutcome> => {
      if (noAvatar) {
        return { status: 'no_avatar' };
      }

      if (!url) {
        return { status: 'error', message: 'Kamera verifikasi belum siap. Coba lagi.' };
      }

      if (resolverRef.current) {
        return { status: 'error', message: 'Verifikasi wajah sedang berjalan.' };
      }

      await waitForPage();

      return new Promise<FaceCaptureOutcome>((resolve) => {
        resolverRef.current = resolve;
        pendingModeRef.current = mode;
        startInjectedRef.current = false;
        setActive(true);

        setTimeout(() => {
          if (!pageLoadedRef.current || startInjectedRef.current) {
            return;
          }

          startInjectedRef.current = true;
          webviewRef.current?.injectJavaScript(
            `window.__faceCaptureStart && window.__faceCaptureStart('${mode}'); true;`,
          );
        }, 80);

        timeoutRef.current = setTimeout(() => {
          finish({ status: 'error', message: 'Verifikasi wajah timeout. Coba lagi.' });
        }, 120000);
      });
    },
    [url, noAvatar, waitForPage, finish],
  );

  const handleMessage = useCallback(
    (event: { nativeEvent: { data: string } }) => {
      let payload: CaptureMessage;

      try {
        payload = JSON.parse(event.nativeEvent.data) as CaptureMessage;
      } catch (error) {
        return;
      }

      if (payload.type === 'loaded') {
        pageLoadedRef.current = true;
        pageLoadedWaiters.current.splice(0).forEach((resolve) => resolve());

        if (resolverRef.current && pendingModeRef.current && !startInjectedRef.current) {
          startInjectedRef.current = true;
          const mode = pendingModeRef.current;
          webviewRef.current?.injectJavaScript(
            `window.__faceCaptureStart && window.__faceCaptureStart('${mode}'); true;`,
          );
        }

        return;
      }

      if (payload.type === 'captured' && payload.photo_data) {
        finish({
          status: 'captured',
          photoData: payload.photo_data,
          faceScore: Number(payload.face_score ?? 0),
        });

        return;
      }

      if (payload.type === 'cancel') {
        finish({ status: 'cancelled' });

        return;
      }

      if (payload.type === 'error' && payload.fatal) {
        finish({
          status: 'error',
          message: payload.message ?? 'Verifikasi wajah gagal.',
        });
      }
    },
    [finish],
  );

  const contextValue = useMemo(() => ({ startFaceCapture }), [startFaceCapture]);

  return (
    <FaceCaptureContext.Provider value={contextValue}>
      <View style={styles.root}>
        {children}

        {url ? (
          <View
            style={[styles.overlay, active ? styles.overlayActive : styles.overlayHidden]}
            pointerEvents={active ? 'auto' : 'none'}
          >
            <WebView
              ref={webviewRef}
              source={{ uri: url }}
              originWhitelist={['about:blank', `${appOrigin}/*`]}
              javaScriptEnabled
              domStorageEnabled
              allowsInlineMediaPlayback
              mediaPlaybackRequiresUserAction={false}
              mediaCapturePermissionGrantType="grant"
              mixedContentMode="never"
              androidLayerType="hardware"
              setSupportMultipleWindows={false}
              onMessage={handleMessage}
              onError={() =>
                finish({ status: 'error', message: 'Gagal memuat kamera verifikasi wajah.' })
              }
              onShouldStartLoadWithRequest={(request) =>
                request.url === 'about:blank' || request.url.startsWith(appOrigin)
              }
              style={styles.webview}
            />
          </View>
        ) : null}
      </View>
    </FaceCaptureContext.Provider>
  );
};

const styles = StyleSheet.create({
  root: { flex: 1 },
  overlay: {
    position: 'absolute',
    backgroundColor: '#000',
    zIndex: 9999,
    elevation: 9999,
  },
  overlayActive: {
    left: 0,
    right: 0,
    top: 0,
    bottom: 0,
    opacity: 1,
  },
  overlayHidden: {
    width: 1,
    height: 1,
    left: -10,
    top: -10,
    opacity: 0,
  },
  webview: { flex: 1, backgroundColor: '#000' },
});
