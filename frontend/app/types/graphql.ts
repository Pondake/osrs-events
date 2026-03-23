export type Maybe<T> = T | null | undefined;
export type InputMaybe<T> = T | null | undefined;
export type Exact<T extends { [key: string]: unknown }> = { [K in keyof T]: T[K] };
export type MakeOptional<T, K extends keyof T> = Omit<T, K> & { [SubKey in K]?: Maybe<T[SubKey]> };
export type MakeMaybe<T, K extends keyof T> = Omit<T, K> & { [SubKey in K]: Maybe<T[SubKey]> };
export type MakeEmpty<T extends { [key: string]: unknown }, K extends keyof T> = { [_ in K]?: never };
export type Incremental<T> = T | { [P in keyof T]?: P extends ' $fragmentName' | '__typename' ? T[P] : never };
/** All built-in and custom scalars, mapped to their actual values */
export type Scalars = {
  ID: { input: string; output: string; }
  String: { input: string; output: string; }
  Boolean: { input: boolean; output: boolean; }
  Int: { input: number; output: number; }
  Float: { input: number; output: number; }
  /** A date-time string at UTC, such as 2019-12-03T09:54:33Z, compliant with the date-time format. */
  DateTime: { input: string; output: string; }
};

export type AddTeamMemberInput = {
  teamId: Scalars['ID']['input'];
  userId: Scalars['ID']['input'];
};

export type BoardAuthorEntity = {
  id: Scalars['ID']['output'];
  isOwner: Scalars['Boolean']['output'];
  user: UserEntity;
};

export type BoardEntity = {
  authors: Array<BoardAuthorEntity>;
  boardTeams?: Maybe<Array<BoardTeamEntity>>;
  createdAt: Scalars['DateTime']['output'];
  description?: Maybe<Scalars['String']['output']>;
  /** null = unlimited rolls per day */
  diceRollLimit?: Maybe<Scalars['Int']['output']>;
  endDate?: Maybe<Scalars['DateTime']['output']>;
  id: Scalars['ID']['output'];
  mode: BoardMode;
  size: BoardSize;
  startDate?: Maybe<Scalars['DateTime']['output']>;
  tiles?: Maybe<Array<TileEntity>>;
  title: Scalars['String']['output'];
  updatedAt: Scalars['DateTime']['output'];
};

export enum BoardMode {
  Solo = 'SOLO',
  Team = 'TEAM'
}

export enum BoardSize {
  Size_5X5 = 'SIZE_5X5',
  Size_7X7 = 'SIZE_7X7',
  Size_9X9 = 'SIZE_9X9'
}

export type BoardTeamEntity = {
  boardId: Scalars['ID']['output'];
  createdAt: Scalars['DateTime']['output'];
  id: Scalars['ID']['output'];
  team: BoardTeamTeamSummary;
  teamId: Scalars['ID']['output'];
};

export type BoardTeamTeamSummary = {
  iconUrl?: Maybe<Scalars['String']['output']>;
  id: Scalars['ID']['output'];
  name: Scalars['String']['output'];
};

export type CompletedTileEntity = {
  completedAt: Scalars['DateTime']['output'];
  completedVia: CompletionSource;
  id: Scalars['ID']['output'];
  tileId: Scalars['ID']['output'];
};

export enum CompletionSource {
  Manual = 'MANUAL',
  Runelite = 'RUNELITE'
}

export type CreateBoardInput = {
  /** UUIDs of admin users to set as authors */
  authorIds: Array<Scalars['ID']['input']>;
  description?: InputMaybe<Scalars['String']['input']>;
  diceRollLimit?: InputMaybe<Scalars['Int']['input']>;
  endDate?: InputMaybe<Scalars['DateTime']['input']>;
  mode?: InputMaybe<BoardMode>;
  size: BoardSize;
  startDate?: InputMaybe<Scalars['DateTime']['input']>;
  title: Scalars['String']['input'];
};

export type CreateTaskInput = {
  description?: InputMaybe<Scalars['String']['input']>;
  iconUrl?: InputMaybe<Scalars['String']['input']>;
  title: Scalars['String']['input'];
};

export type CreateTeamInput = {
  iconUrl?: InputMaybe<Scalars['String']['input']>;
  name: Scalars['String']['input'];
};

export type LeaderboardEntity = {
  boardId: Scalars['ID']['output'];
  entries: Array<LeaderboardEntryEntity>;
  totalTiles: Scalars['Int']['output'];
};

export type LeaderboardEntryEntity = {
  currentPosition: Scalars['Int']['output'];
  pathHasLadder: Scalars['Boolean']['output'];
  pathHasSnake: Scalars['Boolean']['output'];
  playerId: Scalars['ID']['output'];
  rank: Scalars['Int']['output'];
  team?: Maybe<PlayerBoardTeamSummary>;
  tilesRemaining: Scalars['Int']['output'];
  user: UserEntity;
};

export type Mutation = {
  addBoardAuthor: BoardAuthorEntity;
  addTeamMember: TeamEntity;
  addTeamToBoard: BoardTeamEntity;
  assignRole: UserEntity;
  clearSnakeLadder: TileEntity;
  completeTile: PlayerBoardEntity;
  createBoard: BoardEntity;
  createTask: TaskEntity;
  createTeam: TeamEntity;
  deleteBoard: BoardEntity;
  deleteTask: TaskEntity;
  deleteTeam: TeamEntity;
  deleteTile: TileEntity;
  deleteUser: Scalars['Boolean']['output'];
  grantPermission: UserPermissionEntity;
  removeBoardAuthor: Scalars['Boolean']['output'];
  removeRole: UserEntity;
  removeTeamFromBoard: Scalars['Boolean']['output'];
  removeTeamMember: TeamEntity;
  revokePermission: Scalars['Boolean']['output'];
  rollDice: RollResultEntity;
  uncompleteTile?: Maybe<PlayerBoardEntity>;
  updateBoard: BoardEntity;
  updateProfile: UserEntity;
  updateTask: TaskEntity;
  updateTeam: TeamEntity;
  upsertTile: TileEntity;
};


export type MutationAddBoardAuthorArgs = {
  boardId: Scalars['ID']['input'];
  userId: Scalars['ID']['input'];
};


export type MutationAddTeamMemberArgs = {
  input: AddTeamMemberInput;
};


export type MutationAddTeamToBoardArgs = {
  boardId: Scalars['ID']['input'];
  teamId: Scalars['ID']['input'];
};


export type MutationAssignRoleArgs = {
  roleName: Scalars['String']['input'];
  userId: Scalars['ID']['input'];
};


export type MutationClearSnakeLadderArgs = {
  id: Scalars['ID']['input'];
};


export type MutationCompleteTileArgs = {
  boardId: Scalars['ID']['input'];
  tileId: Scalars['ID']['input'];
};


export type MutationCreateBoardArgs = {
  input: CreateBoardInput;
};


export type MutationCreateTaskArgs = {
  input: CreateTaskInput;
};


export type MutationCreateTeamArgs = {
  input: CreateTeamInput;
};


export type MutationDeleteBoardArgs = {
  id: Scalars['ID']['input'];
};


export type MutationDeleteTaskArgs = {
  id: Scalars['ID']['input'];
};


export type MutationDeleteTeamArgs = {
  id: Scalars['ID']['input'];
};


export type MutationDeleteTileArgs = {
  id: Scalars['ID']['input'];
};


export type MutationDeleteUserArgs = {
  userId: Scalars['ID']['input'];
};


export type MutationGrantPermissionArgs = {
  permissionKey: PermissionKey;
  userId: Scalars['ID']['input'];
};


export type MutationRemoveBoardAuthorArgs = {
  boardId: Scalars['ID']['input'];
  userId: Scalars['ID']['input'];
};


export type MutationRemoveRoleArgs = {
  roleName: Scalars['String']['input'];
  userId: Scalars['ID']['input'];
};


export type MutationRemoveTeamFromBoardArgs = {
  boardId: Scalars['ID']['input'];
  teamId: Scalars['ID']['input'];
};


export type MutationRemoveTeamMemberArgs = {
  teamId: Scalars['ID']['input'];
  userId: Scalars['ID']['input'];
};


export type MutationRevokePermissionArgs = {
  permissionKey: PermissionKey;
  userId: Scalars['ID']['input'];
};


export type MutationRollDiceArgs = {
  boardId: Scalars['ID']['input'];
};


export type MutationUncompleteTileArgs = {
  boardId: Scalars['ID']['input'];
  tileId: Scalars['ID']['input'];
};


export type MutationUpdateBoardArgs = {
  id: Scalars['ID']['input'];
  input: UpdateBoardInput;
};


export type MutationUpdateProfileArgs = {
  nickname?: InputMaybe<Scalars['String']['input']>;
};


export type MutationUpdateTaskArgs = {
  id: Scalars['ID']['input'];
  input: UpdateTaskInput;
};


export type MutationUpdateTeamArgs = {
  id: Scalars['ID']['input'];
  input: UpdateTeamInput;
};


export type MutationUpsertTileArgs = {
  input: UpsertTileInput;
};

/** Granular permissions that can be granted to individual users */
export enum PermissionKey {
  CanCreateBoards = 'CAN_CREATE_BOARDS',
  CanCreateTiles = 'CAN_CREATE_TILES'
}

export type PlayerBoardBoardSummary = {
  id: Scalars['ID']['output'];
  size: Scalars['String']['output'];
  title: Scalars['String']['output'];
};

export type PlayerBoardEntity = {
  board?: Maybe<PlayerBoardBoardSummary>;
  boardId: Scalars['ID']['output'];
  completedTiles: Array<CompletedTileEntity>;
  createdAt: Scalars['DateTime']['output'];
  currentPosition: Scalars['Int']['output'];
  diceRollsToday: Scalars['Int']['output'];
  id: Scalars['ID']['output'];
  lastRollDate?: Maybe<Scalars['DateTime']['output']>;
  team?: Maybe<PlayerBoardTeamSummary>;
  teamId?: Maybe<Scalars['ID']['output']>;
  updatedAt: Scalars['DateTime']['output'];
  user?: Maybe<UserEntity>;
  userId: Scalars['ID']['output'];
};

export type PlayerBoardTeamSummary = {
  iconUrl?: Maybe<Scalars['String']['output']>;
  id: Scalars['ID']['output'];
  name: Scalars['String']['output'];
};

export type Query = {
  board?: Maybe<BoardEntity>;
  boardLeaderboard?: Maybe<LeaderboardEntity>;
  boardPlayerStates: Array<PlayerBoardEntity>;
  boards: Array<BoardEntity>;
  me?: Maybe<UserEntity>;
  myBoardState?: Maybe<PlayerBoardEntity>;
  myPermissions: Array<UserPermissionEntity>;
  myPlayerBoards: Array<PlayerBoardEntity>;
  myTeams: Array<TeamEntity>;
  task?: Maybe<TaskEntity>;
  tasks: Array<TaskEntity>;
  team?: Maybe<TeamEntity>;
  teams: Array<TeamEntity>;
  tiles: Array<TileEntity>;
  userPermissions: Array<UserPermissionEntity>;
  users: Array<UserEntity>;
};


export type QueryBoardArgs = {
  id: Scalars['ID']['input'];
};


export type QueryBoardLeaderboardArgs = {
  boardId: Scalars['ID']['input'];
};


export type QueryBoardPlayerStatesArgs = {
  boardId: Scalars['ID']['input'];
};


export type QueryMyBoardStateArgs = {
  boardId: Scalars['ID']['input'];
};


export type QueryTaskArgs = {
  id: Scalars['ID']['input'];
};


export type QueryTasksArgs = {
  search?: InputMaybe<Scalars['String']['input']>;
};


export type QueryTeamArgs = {
  id: Scalars['ID']['input'];
};


export type QueryTilesArgs = {
  boardId: Scalars['ID']['input'];
};


export type QueryUserPermissionsArgs = {
  userId: Scalars['ID']['input'];
};


export type QueryUsersArgs = {
  limit?: InputMaybe<Scalars['Float']['input']>;
  search?: InputMaybe<Scalars['String']['input']>;
};

export type RoleEntity = {
  description?: Maybe<Scalars['String']['output']>;
  id: Scalars['ID']['output'];
  name: Scalars['String']['output'];
};

export type RollResultEntity = {
  /** snake | ladder | null */
  jump?: Maybe<Scalars['String']['output']>;
  /** Position before snake/ladder jump */
  landedOn?: Maybe<Scalars['Int']['output']>;
  newPosition: Scalars['Int']['output'];
  playerBoard: PlayerBoardEntity;
  previousPosition: Scalars['Int']['output'];
  rolled: Scalars['Int']['output'];
};

export type TaskEntity = {
  createdAt: Scalars['DateTime']['output'];
  description?: Maybe<Scalars['String']['output']>;
  iconUrl?: Maybe<Scalars['String']['output']>;
  id: Scalars['ID']['output'];
  title: Scalars['String']['output'];
  updatedAt: Scalars['DateTime']['output'];
};

export type TeamEntity = {
  createdAt: Scalars['DateTime']['output'];
  iconUrl?: Maybe<Scalars['String']['output']>;
  id: Scalars['ID']['output'];
  members: Array<TeamMemberEntity>;
  name: Scalars['String']['output'];
  updatedAt: Scalars['DateTime']['output'];
};

export type TeamMemberEntity = {
  createdAt: Scalars['DateTime']['output'];
  id: Scalars['ID']['output'];
  user: UserEntity;
  userId: Scalars['ID']['output'];
};

export type TileEntity = {
  boardId: Scalars['ID']['output'];
  createdAt: Scalars['DateTime']['output'];
  displayTitle?: Maybe<Scalars['String']['output']>;
  iconUrl?: Maybe<Scalars['String']['output']>;
  id: Scalars['ID']['output'];
  position: Scalars['Int']['output'];
  targetPosition?: Maybe<Scalars['Int']['output']>;
  task?: Maybe<TaskEntity>;
  titleOverride?: Maybe<Scalars['String']['output']>;
  type: TileType;
  updatedAt: Scalars['DateTime']['output'];
};

export enum TileType {
  Ladder = 'LADDER',
  Normal = 'NORMAL',
  Snake = 'SNAKE'
}

export type UpdateBoardInput = {
  authorIds?: InputMaybe<Array<Scalars['ID']['input']>>;
  description?: InputMaybe<Scalars['String']['input']>;
  diceRollLimit?: InputMaybe<Scalars['Int']['input']>;
  endDate?: InputMaybe<Scalars['DateTime']['input']>;
  mode?: InputMaybe<BoardMode>;
  size?: InputMaybe<BoardSize>;
  startDate?: InputMaybe<Scalars['DateTime']['input']>;
  title?: InputMaybe<Scalars['String']['input']>;
};

export type UpdateTaskInput = {
  description?: InputMaybe<Scalars['String']['input']>;
  iconUrl?: InputMaybe<Scalars['String']['input']>;
  title?: InputMaybe<Scalars['String']['input']>;
};

export type UpdateTeamInput = {
  iconUrl?: InputMaybe<Scalars['String']['input']>;
  name?: InputMaybe<Scalars['String']['input']>;
};

export type UpsertTileInput = {
  boardId: Scalars['ID']['input'];
  position: Scalars['Int']['input'];
  targetPosition?: InputMaybe<Scalars['Int']['input']>;
  taskId?: InputMaybe<Scalars['ID']['input']>;
  titleOverride?: InputMaybe<Scalars['String']['input']>;
  type?: InputMaybe<TileType>;
};

export type UserEntity = {
  avatarUrl?: Maybe<Scalars['String']['output']>;
  createdAt: Scalars['DateTime']['output'];
  discordId: Scalars['String']['output'];
  discordUsername: Scalars['String']['output'];
  id: Scalars['ID']['output'];
  nickname?: Maybe<Scalars['String']['output']>;
  updatedAt: Scalars['DateTime']['output'];
  userRoles: Array<UserRoleEntity>;
};

export type UserPermissionEntity = {
  createdAt: Scalars['DateTime']['output'];
  id: Scalars['ID']['output'];
  permissionKey: PermissionKey;
  userId: Scalars['ID']['output'];
};

export type UserRoleEntity = {
  id: Scalars['ID']['output'];
  role: RoleEntity;
};
