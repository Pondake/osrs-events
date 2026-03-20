import { NestFactory } from '@nestjs/core'
import { AppModule } from './app.module'
import type { Request, Response } from 'express'

// Bootstrap the Nest app once; promise is cached at module level so warm
// serverless invocations skip the expensive init on subsequent requests.
let nestApp: Awaited<ReturnType<typeof NestFactory.create>> | undefined

const bootstrapPromise = (async () => {
  nestApp = await NestFactory.create(AppModule, { logger: ['error', 'warn', 'log'] })

  nestApp.enableCors({
    origin: process.env.NUXT_APP_URL || 'http://localhost:3000',
    credentials: true,
  })

  await nestApp.init()
  return nestApp
})()

// ─────────────────────────────────────────────
// Vercel serverless handler — required export for deployment
// Without this, Vercel throws "No exports found in module main.js"
// ─────────────────────────────────────────────
export default async function handler(req: Request, res: Response) {
  await bootstrapPromise
  // Get the underlying Express instance NestJS created internally
  nestApp!.getHttpAdapter().getInstance()(req, res)
}

// ─────────────────────────────────────────────
// Local development — starts a real HTTP server
// ─────────────────────────────────────────────
if (process.env.NODE_ENV !== 'production') {
  bootstrapPromise.then(async () => {
    const port = process.env.PORT || 3001
    await nestApp!.listen(port)
    console.log(`🚀 Backend running on http://localhost:${port}`)
    console.log(`📊 GraphQL Playground: http://localhost:${port}/graphql`)
  })
}
