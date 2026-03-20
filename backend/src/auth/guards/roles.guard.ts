import { Injectable, CanActivate, ExecutionContext } from '@nestjs/common'
import { Reflector } from '@nestjs/core'
import { GqlExecutionContext } from '@nestjs/graphql'
import { ROLES_KEY } from '../decorators/roles.decorator'

@Injectable()
export class RolesGuard implements CanActivate {
  constructor(private reflector: Reflector) {}

  canActivate(context: ExecutionContext): boolean {
    const requiredRoles = this.reflector.getAllAndOverride<string[]>(ROLES_KEY, [
      context.getHandler(),
      context.getClass()
    ])

    if (!requiredRoles || requiredRoles.length === 0) {
      return true
    }

    const ctx = GqlExecutionContext.create(context)
    const user = ctx.getContext().req.user

    if (!user) return false

    const userRoleNames = (user.userRoles || []).map((ur: { role: { name: string } }) => ur.role.name)
    return requiredRoles.some((role) => userRoleNames.includes(role))
  }
}
