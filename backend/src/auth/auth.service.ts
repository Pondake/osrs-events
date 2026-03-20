import { Injectable, Logger } from '@nestjs/common'
import { JwtService } from '@nestjs/jwt'
import { ConfigService } from '@nestjs/config'
import { HttpService } from '@nestjs/axios'
import { firstValueFrom } from 'rxjs'
import { UsersService } from '../users/users.service'
import type { JwtPayload } from './strategies/jwt.strategy'

interface DiscordTokenResponse {
  access_token: string
  token_type: string
  expires_in: number
  refresh_token: string
  scope: string
}

interface DiscordUser {
  id: string
  username: string
  avatar: string | null
  discriminator: string
}

@Injectable()
export class AuthService {
  private readonly logger = new Logger(AuthService.name)

  constructor(
    private jwtService: JwtService,
    private configService: ConfigService,
    private httpService: HttpService,
    private usersService: UsersService
  ) {}

  /**
   * Exchange Discord authorization code for an access token and user data.
   * Creates or updates the user in our database.
   * Returns a signed JWT.
   */
  async handleDiscordCallback(code: string): Promise<string> {
    const clientId = this.configService.getOrThrow<string>('DISCORD_CLIENT_ID')
    const clientSecret = this.configService.getOrThrow<string>('DISCORD_CLIENT_SECRET')
    const callbackUrl = this.configService.getOrThrow<string>('DISCORD_CALLBACK_URL')

    // Exchange code for Discord access token
    const tokenResponse = await firstValueFrom(
      this.httpService.post<DiscordTokenResponse>(
        'https://discord.com/api/oauth2/token',
        new URLSearchParams({
          client_id: clientId,
          client_secret: clientSecret,
          grant_type: 'authorization_code',
          code,
          redirect_uri: callbackUrl
        }),
        { headers: { 'Content-Type': 'application/x-www-form-urlencoded' } }
      )
    )

    const discordAccessToken = tokenResponse.data.access_token

    // Fetch Discord user info
    const userResponse = await firstValueFrom(
      this.httpService.get<DiscordUser>('https://discord.com/api/users/@me', {
        headers: { Authorization: `Bearer ${discordAccessToken}` }
      })
    )

    const discordUser = userResponse.data
    const avatarUrl = discordUser.avatar
      ? `https://cdn.discordapp.com/avatars/${discordUser.id}/${discordUser.avatar}.png`
      : null

    // Upsert user in database
    const user = await this.usersService.upsertFromDiscord({
      discordId: discordUser.id,
      discordUsername: discordUser.username,
      avatarUrl
    })

    // Sign and return JWT
    const payload: JwtPayload = { sub: user.id, discordId: user.discordId }
    return this.jwtService.sign(payload)
  }

  /**
   * Build the Discord OAuth2 authorization URL
   */
  getDiscordAuthUrl(): string {
    const clientId = this.configService.getOrThrow<string>('DISCORD_CLIENT_ID')
    const callbackUrl = this.configService.getOrThrow<string>('DISCORD_CALLBACK_URL')
    const params = new URLSearchParams({
      client_id: clientId,
      redirect_uri: callbackUrl,
      response_type: 'code',
      scope: 'identify'
    })
    return `https://discord.com/api/oauth2/authorize?${params.toString()}`
  }
}
