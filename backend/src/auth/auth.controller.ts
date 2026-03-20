import { Controller, Get, Query, Res, UseGuards, Req } from '@nestjs/common'
import type { Response, Request } from 'express'
import { ConfigService } from '@nestjs/config'
import { AuthService } from './auth.service'
import { JwtAuthGuard } from './guards/jwt-auth.guard'

@Controller('auth')
export class AuthController {
  constructor(
    private authService: AuthService,
    private configService: ConfigService
  ) {}

  /**
   * Redirect user to Discord OAuth2 login page
   */
  @Get('discord')
  discordLogin(@Res() res: Response) {
    const url = this.authService.getDiscordAuthUrl()
    return res.redirect(url)
  }

  /**
   * Discord redirects back here with ?code=...
   * We exchange it for a JWT and redirect to the frontend
   */
  @Get('discord/callback')
  async discordCallback(@Query('code') code: string, @Res() res: Response) {
    // NUXT_APP_URL is the Nuxt frontend (port 3000) — the backend redirects there after auth
    const frontendUrl = this.configService.getOrThrow<string>('NUXT_APP_URL')
    try {
      const token = await this.authService.handleDiscordCallback(code)
      return res.redirect(`${frontendUrl}/auth/callback?token=${token}`)
    }
    catch (err: unknown) {
      const message = err instanceof Error ? err.message : String(err)
      console.error('[Auth] Discord callback failed:', message)
      if (err instanceof Error && err.stack) console.error(err.stack)
      // Redirect to the callback page so it can display the error toast
      return res.redirect(`${frontendUrl}/auth/callback?error=auth_failed`)
    }
  }

  /**
   * Returns the current authenticated user's info
   */
  @UseGuards(JwtAuthGuard)
  @Get('me')
  getMe(@Req() req: Request & { user: Record<string, unknown> }) {
    const user = req.user as {
      id: string
      discordId: string
      discordUsername: string
      nickname: string | null
      avatarUrl: string | null
      userRoles: Array<{ role: { name: string } }>
    }
    return {
      id: user.id,
      discordId: user.discordId,
      discordUsername: user.discordUsername,
      nickname: user.nickname ?? null,
      avatarUrl: user.avatarUrl,
      roles: user.userRoles.map((ur) => ur.role.name)
    }
  }
}
