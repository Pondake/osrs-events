import * as z from 'zod';

import type { AuthorOption, AssignedTeam } from '~/components/Board/SettingsForm.vue';

export const BOARD_SIZES = ['SIZE_5X5', 'SIZE_7X7', 'SIZE_9X9'] as const;
export const BOARD_MODES = ['SOLO', 'TEAM'] as const;
export const BOARD_ACCESS_MODES = ['OPEN', 'GUILD', 'INVITE'] as const;

export const BOARD_TITLE_MAX = 100;
export const BOARD_DESCRIPTION_MAX = 500;
export const DICE_ROLL_MIN = 1;
export const DICE_ROLL_MAX = 99;

/**
 * Which schema fields belong to which step of the create wizard / tab of the
 * edit view. Used to validate one step at a time via `form.validate({ name })`
 * so the user only sees errors for the section they are actually on.
 */
export const BOARD_STEP_FIELDS = {
  basics: ['title', 'description', 'size', 'mode'],
  schedule: ['startDate', 'endDate', 'diceRollLimit', 'unlimitedRolls'],
  access: ['isListed', 'accessMode', 'requiredGuildId'],
  editors: ['selectedAuthors', 'assignedTeams'],
} as const satisfies Record<string, readonly string[]>;

export type BoardStep = keyof typeof BOARD_STEP_FIELDS;

/** Steps in wizard order — the create stepper walks these in sequence. */
export const BOARD_STEP_ORDER = ['basics', 'schedule', 'access', 'editors'] as const;

type Translate = (key: string, named?: Record<string, unknown>) => string;

/**
 * Built as a factory rather than a module-level constant so validation messages
 * go through i18n. Create and edit share it: create validates a step at a time,
 * edit validates the whole thing on save.
 */
export function createBoardSchema(t: Translate) {
  return z
    .object({
      title: z
        .string()
        .trim()
        .min(1, t('validation.title_required'))
        .max(BOARD_TITLE_MAX, t('validation.title_too_long', { max: BOARD_TITLE_MAX })),

      description: z
        .string()
        .trim()
        .max(
          BOARD_DESCRIPTION_MAX,
          t('validation.description_too_long', { max: BOARD_DESCRIPTION_MAX }),
        ),

      size: z.enum(BOARD_SIZES),
      mode: z.enum(BOARD_MODES),

      // YYYY-MM-DD, or null when the organiser leaves the window open.
      startDate: z.string().nullable(),
      endDate: z.string().nullable(),

      diceRollLimit: z.number(),
      unlimitedRolls: z.boolean(),

      selectedAuthors: z.array(z.custom<AuthorOption>()).min(1, t('validation.editors_required')),
      assignedTeams: z.array(z.custom<AssignedTeam>()),

      isListed: z.boolean(),
      accessMode: z.enum(BOARD_ACCESS_MODES),
      requiredGuildId: z.string().nullable(),
    })
    .superRefine((data, ctx) => {
      // ISO YYYY-MM-DD compares correctly as a plain string.
      if (data.startDate && data.endDate && data.endDate < data.startDate) {
        ctx.addIssue({
          code: 'custom',
          message: t('validation.end_before_start'),
          path: ['startDate'],
        });
      }

      // The dice limit only matters when the board is not set to unlimited.
      if (
        !data.unlimitedRolls &&
        (!Number.isInteger(data.diceRollLimit) ||
          data.diceRollLimit < DICE_ROLL_MIN ||
          data.diceRollLimit > DICE_ROLL_MAX)
      ) {
        ctx.addIssue({
          code: 'custom',
          message: t('validation.dice_limit_range', { min: DICE_ROLL_MIN, max: DICE_ROLL_MAX }),
          path: ['diceRollLimit'],
        });
      }

      // A guild-gated board is meaningless without the guild to gate on.
      if (data.accessMode === 'GUILD' && !data.requiredGuildId) {
        ctx.addIssue({
          code: 'custom',
          message: t('validation.server_required'),
          path: ['requiredGuildId'],
        });
      }
    });
}

export type BoardSchema = z.output<ReturnType<typeof createBoardSchema>>;
