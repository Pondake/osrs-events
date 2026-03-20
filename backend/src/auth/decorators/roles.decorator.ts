import { SetMetadata } from '@nestjs/common'

export const ROLES_KEY = 'roles'

/**
 * Decorator to restrict a resolver/controller method to specific roles
 * Usage: @Roles('ADMIN', 'EDITOR')
 */
export const Roles = (...roles: string[]) => SetMetadata(ROLES_KEY, roles)
