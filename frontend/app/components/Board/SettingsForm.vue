<script setup lang="ts">
import type { CalendarDate } from '@internationalized/date'
import type { UserEntity, BoardTeamEntity } from '~/types/graphql'

// Pick<BoardTeamEntity, 'teamId' | 'team'> matches exactly — no manual shape needed.
export type AssignedTeam = Pick<BoardTeamEntity, 'teamId' | 'team'>

// Pick<UserEntity, ...> matches exactly — no manual shape needed.
export type AuthorOption = Pick<UserEntity, 'id' | 'discordUsername' | 'avatarUrl'>

export interface BoardFormData {
  title: string
  description: string
  size: 'SIZE_5X5' | 'SIZE_7X7' | 'SIZE_9X9'
  mode: 'SOLO' | 'TEAM'
  diceRollLimit: number
  unlimitedRolls: boolean
  selectedAuthors: AuthorOption[]
  assignedTeams: AssignedTeam[]
  startDate: CalendarDate | null
  endDate: CalendarDate | null
  isListed: boolean
  accessMode: 'OPEN' | 'GUILD' | 'INVITE'
  requiredGuildId: string | null
}
</script>
