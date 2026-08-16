// ─── Field selections ─────────────────────────────────────────────────────────

const TEAM_MEMBER_FIELDS = `
  id userId
  user { id discordUsername nickname avatarUrl }
`;

const TEAM_FIELDS = `
  id name iconUrl guildId guildName createdAt updatedAt
  members { ${TEAM_MEMBER_FIELDS} }
`;

// ─── Queries & mutations ──────────────────────────────────────────────────────

const MY_TEAMS_QUERY = `
  query MyTeams {
    myTeams { ${TEAM_FIELDS} }
  }
`;

const ALL_TEAMS_QUERY = `
  query Teams {
    teams { ${TEAM_FIELDS} }
  }
`;

const TEAM_QUERY = `
  query Team($id: ID!) {
    team(id: $id) { ${TEAM_FIELDS} }
  }
`;

const CREATE_TEAM_MUTATION = `
  mutation CreateTeam($input: CreateTeamInput!) {
    createTeam(input: $input) { ${TEAM_FIELDS} }
  }
`;

const UPDATE_TEAM_MUTATION = `
  mutation UpdateTeam($id: ID!, $input: UpdateTeamInput!) {
    updateTeam(id: $id, input: $input) { ${TEAM_FIELDS} }
  }
`;

const DELETE_TEAM_MUTATION = `
  mutation DeleteTeam($id: ID!) {
    deleteTeam(id: $id) { id }
  }
`;

const ADD_TEAM_MEMBER_MUTATION = `
  mutation AddTeamMember($input: AddTeamMemberInput!) {
    addTeamMember(input: $input) { ${TEAM_FIELDS} }
  }
`;

const REMOVE_TEAM_MEMBER_MUTATION = `
  mutation RemoveTeamMember($teamId: ID!, $userId: ID!) {
    removeTeamMember(teamId: $teamId, userId: $userId) { id }
  }
`;

// ─── Types ────────────────────────────────────────────────────────────────────
// TeamMemberEntity and TeamEntity exist in ~/types/graphql but the GQL query
// only selects a subset of their fields (e.g. TeamMemberEntity includes
// `createdAt` which we don't query). These manual types exactly mirror the
// query field selections — don't replace them with the full entity types or
// TypeScript will expect fields that aren't in the response.

export interface TeamMemberData {
  id: string;
  userId: string;
  user: { id: string; discordUsername: string; nickname: string | null; avatarUrl: string | null };
}

export interface TeamData {
  id: string;
  name: string;
  iconUrl: string | null;
  guildId: string | null;
  guildName: string | null;
  createdAt: string;
  updatedAt: string;
  members: TeamMemberData[];
}

// ─── Composables ──────────────────────────────────────────────────────────────

/**
 * The current user's teams.
 */
export function useMyTeams() {
  const teams = ref<TeamData[]>([]);
  const loading = ref(false);

  async function load() {
    loading.value = true;
    try {
      const result = await useGqlMutation<{ myTeams: TeamData[] }>(MY_TEAMS_QUERY);
      teams.value = result.myTeams ?? [];
    } catch {
      teams.value = [];
    } finally {
      loading.value = false;
    }
  }

  async function createTeam(input: {
    name: string;
    iconUrl?: string | null;
    guildId?: string | null;
    guildName?: string | null;
  }): Promise<TeamData> {
    const result = await useGqlMutation<{ createTeam: TeamData }>(CREATE_TEAM_MUTATION, { input });
    return result.createTeam;
  }

  async function updateTeam(
    id: string,
    input: {
      name?: string;
      iconUrl?: string | null;
      guildId?: string | null;
      guildName?: string | null;
    },
  ): Promise<TeamData> {
    const result = await useGqlMutation<{ updateTeam: TeamData }>(UPDATE_TEAM_MUTATION, {
      id,
      input,
    });
    return result.updateTeam;
  }

  async function deleteTeam(id: string): Promise<void> {
    await useGqlMutation(DELETE_TEAM_MUTATION, { id });
  }

  async function addTeamMember(teamId: string, userId: string): Promise<TeamData> {
    const result = await useGqlMutation<{ addTeamMember: TeamData }>(ADD_TEAM_MEMBER_MUTATION, {
      input: { teamId, userId },
    });
    return result.addTeamMember;
  }

  async function removeTeamMember(teamId: string, userId: string): Promise<void> {
    await useGqlMutation(REMOVE_TEAM_MEMBER_MUTATION, { teamId, userId });
  }

  return {
    teams,
    loading,
    load,
    createTeam,
    updateTeam,
    deleteTeam,
    addTeamMember,
    removeTeamMember,
  };
}

/**
 * All teams — admin / team manager view.
 * Has the same interface as useMyTeams so pages can swap composables freely.
 */
export function useAllTeams() {
  const teams = ref<TeamData[]>([]);
  const loading = ref(false);

  async function load() {
    loading.value = true;
    try {
      const result = await useGqlMutation<{ teams: TeamData[] }>(ALL_TEAMS_QUERY);
      teams.value = result.teams ?? [];
    } catch {
      teams.value = [];
    } finally {
      loading.value = false;
    }
  }

  async function createTeam(input: {
    name: string;
    iconUrl?: string | null;
    guildId?: string | null;
    guildName?: string | null;
  }): Promise<TeamData> {
    const result = await useGqlMutation<{ createTeam: TeamData }>(CREATE_TEAM_MUTATION, { input });
    return result.createTeam;
  }

  async function updateTeam(
    id: string,
    input: {
      name?: string;
      iconUrl?: string | null;
      guildId?: string | null;
      guildName?: string | null;
    },
  ): Promise<TeamData> {
    const result = await useGqlMutation<{ updateTeam: TeamData }>(UPDATE_TEAM_MUTATION, {
      id,
      input,
    });
    return result.updateTeam;
  }

  async function deleteTeam(id: string): Promise<void> {
    await useGqlMutation(DELETE_TEAM_MUTATION, { id });
  }

  async function addTeamMember(teamId: string, userId: string): Promise<TeamData> {
    const result = await useGqlMutation<{ addTeamMember: TeamData }>(ADD_TEAM_MEMBER_MUTATION, {
      input: { teamId, userId },
    });
    return result.addTeamMember;
  }

  async function removeTeamMember(teamId: string, userId: string): Promise<void> {
    await useGqlMutation(REMOVE_TEAM_MEMBER_MUTATION, { teamId, userId });
  }

  return {
    teams,
    loading,
    load,
    createTeam,
    updateTeam,
    deleteTeam,
    addTeamMember,
    removeTeamMember,
  };
}
