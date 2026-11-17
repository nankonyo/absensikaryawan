export type TabsParamList = {
  Dashboard: undefined;
  Attendance: undefined;
  Leave: undefined;
  Team: undefined;
  Approval: undefined;
  InternalTab: undefined;
  More: undefined;
  Profile: undefined;
};

export type InternalStackParamList = {
  InternalHome: undefined;
  Projects: undefined;
  ProjectDetail: { id: number };
  Kanban: undefined;
  Gantt: undefined;
  Activities: undefined;
  Contents: undefined;
};

export type MoreStackParamList = {
  MoreHome: undefined;
  AdminResource: { resource: string; title: string };
};
