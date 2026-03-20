import 'reflect-metadata'
import { NestFactory } from '@nestjs/core'
import { AppModule } from './app.module'
import type { Request, Response, NextFunction } from 'express'

// Cached NestJS app instance — reused across warm Vercel Lambda invocations.
let app: Awaited<ReturnType<typeof NestFactory.create>> | null = null

async function bootstrap() {
  const nestApp = await NestFactory.create(AppModule, {
    logger: process.env.NODE_ENV === 'production' ? ['error', 'warn'] : undefined,
  })

  // Restrict CORS to the frontend origin only — never use origin: true in production
  const allowedOrigin = process.env.NUXT_APP_URL || 'http://localhost:3000'

  nestApp.enableCors({
    origin: allowedOrigin,
    credentials: true,
    methods: ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    allowedHeaders: ['Content-Type', 'Authorization', 'Accept'],
  }) 

  // Block direct browser / scraper access in production.
  // Discord OAuth endpoints are explicitly excluded — they are public redirects.
  // All other requests must show at least one sign of a legitimate API call:
  //   1. POST with Content-Type: application/json  (GraphQL, Nuxt SSR)
  //   2. Authorization: Bearer <token>             (authenticated requests)
  //   3. Origin matches the frontend URL           (browser SPA requests)
  if (process.env.NODE_ENV === 'production') {
    nestApp.use((req: Request, res: Response, next: NextFunction) => {
      // Always allow Discord OAuth redirect flow and CORS preflight
      if (req.path.startsWith('/auth/discord') || req.method === 'OPTIONS') {
        return next()
      }

      const hasAuth   = !!req.headers.authorization
      const hasJson   = req.headers['content-type']?.includes('application/json')
      const fromFront = req.headers.origin === allowedOrigin

      if (!hasAuth && !hasJson && !fromFront) {
        return res.status(403).json({ message: 'Direct API access is not permitted.' })
      }

      next()
    })
  }

  return nestApp
}

// Vercel serverless handler — @vercel/node compiles this from TypeScript source.
// The app is initialised once and reused across warm invocations.
export default async function handler(req: Request, res: Response) {
  if (!app) {
    app = await bootstrap()
    await app.init()
  }
  app.getHttpAdapter().getInstance()(req, res)
}

// Local development: spin up a real HTTP server.
if (process.env.NODE_ENV !== 'production') {
  bootstrap().then(async (nestApp) => {
    const port = process.env.PORT || 3001
    await nestApp.listen(port)
    console.log(`🚀 Backend running on http://localhost:${port}`)
    console.log(`📊 GraphQL Playground: http://localhost:${port}/graphql`)
  })
}
