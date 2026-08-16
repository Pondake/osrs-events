<script setup lang="ts">
import type { UserEntity, BoardTeamEntity } from '~/types/graphql';

// Pick<BoardTeamEntity, 'teamId' | 'team'> matches exactly — no manual shape needed.
export type AssignedTeam = Pick<BoardTeamEntity, 'teamId' | 'team'>;

// Pick<UserEntity, ...> matches exactly — no manual shape needed.
export type AuthorOption = Pick<UserEntity, 'id' | 'discordUsername' | 'avatarUrl'>;

export interface BoardFormData {
  title: string;
  description: string;
  size: 'SIZE_5X5' | 'SIZE_7X7' | 'SIZE_9X9';
  mode: 'SOLO' | 'TEAM';
  diceRollLimit: number;
  unlimitedRolls: boolean;
  selectedAuthors: AuthorOption[];
  assignedTeams: AssignedTeam[];
  // Plain ISO calendar dates (YYYY-MM-DD). Deliberately not CalendarDate:
  // vue-tsc resolves imported types structurally when they cross defineProps,
  // which strips the class's private brand and makes CalendarDate stop
  // matching itself. Conversion happens inside ScheduleSection instead.
  startDate: string | null;
  endDate: string | null;
  isListed: boolean;
  accessMode: 'OPEN' | 'GUILD' | 'INVITE';
  requiredGuildId: string | null;
}
</script>
