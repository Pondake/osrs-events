import { NestFactory } from '@nestjs/core'
import { AppModule } from './app.module'

let nestApp: Awaited<ReturnType<typeof NestFactory.create>> | undefined

const bootstrapPromise = (async () => {
  nestApp = await NestFactory.create(AppModule, { logger: ['error', 'warn', 'log'] })

  nestApp.enableCors({
    origin: process.env.NUXT_APP_URL || 'http://localhost:3000',
    credentials: true,
    methods: ['GET', 'POST', 'OPTIONS'],
    allowedHeaders: ['Content-Type', 'Authorization'],
  })

  await nestApp.init()
  return nestApp
})()

// ─────────────────────────────────────────────
// Vercel serverless handler
// ─────────────────────────────────────────────
// eslint-disable-next-line @typescript-eslint/no-explicit-any
export default async function handler(req: any, res: any) {
  const origin = process.env.NUXT_APP_URL || 'http://localhost:3000'

  // Set CORS headers at the outermost layer — this guarantees they are present
  // on every response, including errors and cold-start failures, because the
  // NestJS middleware only runs after bootstrapPromise resolves.
  res.setHeader('Access-Control-Allow-Origin', origin)
  res.setHeader('Access-Control-Allow-Credentials', 'true')
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')

  // Respond to CORS preflight immediately — no need to start NestJS for this
  if (req.method === 'OPTIONS') {
    res.statusCode = 204
    res.end()
    return
  }

  await bootstrapPromise
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
