<!DOCTYPE html>
<html lang="id">
<head><meta charset="utf-8"><title>Laporan Aktivitas Tim</title></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <div style="max-width:760px;margin:0 auto;padding:28px 16px;">
        <div style="background:#0f172a;border-radius:18px 18px 0 0;padding:26px 28px;color:#fff;">
            <p style="margin:0;font-size:12px;letter-spacing:1.4px;text-transform:uppercase;color:#cbd5e1;">Laporan Aktivitas Tim</p>
            <h1 style="margin:8px 0 4px;font-size:25px;line-height:1.25;">{{ $appName }}</h1>
            <p style="margin:0;font-size:13px;color:#cbd5e1;">{{ $periodLabel }}</p>
        </div>

        <div style="background:#ffffff;padding:26px 28px;border-left:1px solid #e2e8f0;border-right:1px solid #e2e8f0;">
            <p style="margin:0 0 4px;font-size:13px;color:#64748b;">Kepada,</p>
            <p style="margin:0;font-size:17px;font-weight:bold;color:#0f172a;">{{ $supervisor['name'] }}</p>
            <p style="margin:4px 0 0;font-size:13px;color:#64748b;">Berikut ringkasan aktivitas anggota tim Anda pada periode laporan.</p>

            <div style="height:1px;background:#e2e8f0;margin:22px 0;"></div>

            @foreach($members as $member)
                <div style="margin-bottom:16px;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;">
                    <div style="background:#f8fafc;padding:13px 16px;">
                        <p style="margin:0;font-size:14px;font-weight:bold;color:#0f172a;">{{ $member['name'] }}</p>
                        <p style="margin:2px 0 0;font-size:11px;color:#64748b;">{{ $member['email'] ?: '-' }} · {{ count($member['activities']) }} aktivitas</p>
                    </div>
                    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                        @foreach($member['activities'] as $activity)
                            <tr>
                                <td style="padding:11px 16px;border-top:1px solid #f1f5f9;width:92px;vertical-align:top;font-size:11px;color:#64748b;">
                                    {{ \Carbon\Carbon::parse($activity->activity_date)->format('d/m/Y') }}
                                </td>
                                <td style="padding:11px 16px;border-top:1px solid #f1f5f9;vertical-align:top;">
                                    <p style="margin:0;font-size:13px;font-weight:600;color:#0f172a;">
                                        {{ $activity->description ?: ucfirst($activity->action) }}
                                    </p>
                                    <p style="margin:3px 0 0;font-size:11px;color:#94a3b8;">
                                        {{ $activity->project_name ?: 'Tanpa project' }}
                                        @if($activity->task_title) · {{ $activity->task_title }} @endif
                                    </p>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endforeach

            <p style="margin:22px 0 0;font-size:11px;color:#94a3b8;">
                Email ini dikirim otomatis berdasarkan pengaturan laporan aktivitas. Jika tidak ada aktivitas pada periode berikutnya, laporan tidak akan dikirim.
            </p>
        </div>

        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-top:0;border-radius:0 0 18px 18px;padding:16px 28px;">
            <p style="margin:0;font-size:11px;color:#94a3b8;">© {{ date('Y') }} {{ $appName }} · Laporan Aktivitas Otomatis</p>
        </div>
    </div>
</body>
</html>
