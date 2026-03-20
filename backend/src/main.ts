import 'reflect-metadata'
import { NestFactory } from '@nestjs/core'
import { ExpressAdapter } from '@nestjs/platform-express'
import { AppModule } from './app.module'
import express from 'express'
import type { Request, Response } from 'express'

// One shared Express instance — reused across warm Lambda invocations.
const server = express()
let nestInitialized = false

async function initNest(): Promise<void> {
  if (nestInitialized) return
  nestInitialized = true

  const app = await NestFactory.create(AppModule, new ExpressAdapter(server), {
    logger: process.env.NODE_ENV === 'production' ? ['error', 'warn'] : undefined,
  })

  // CORS: origin:true reflects the request origin back, allowing all origins
  // while remaining compatible with Authorization headers.
  // Lock this down to specific domains before going to production:
  //   origin: ['https://osrs-events.com', 'https://dev.osrs-events.com', 'http://localhost:3000']
  app.enableCors({
    origin: true,
    methods: ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    allowedHeaders: ['Content-Type', 'Authorization', 'Accept'],
  })

  await app.init()
}

// ── Vercel serverless handler ─────────────────────────────────────────────
// @vercel/node compiles this file with SWC (which supports emitDecoratorMetadata)
// and calls this export for every incoming request.
export default async function handler(req: Request, res: Response): Promise<void> {
  await initNest()
  server(req, res)
}

// ── Local development: start a real HTTP server ───────────────────────────
// nest start / nest start:dev executes this file directly. We detect non-production
// by checking NODE_ENV and start a proper listening server.
async function bootstrap(): Promise<void> {
  await initNest()
  const port = process.env.PORT ?? 3001
  server.listen(port, () => {
    console.log(`🚀 Backend running on http://localhost:${port}`)
    console.log(`📊 GraphQL Playground: http://localhost:${port}/graphql`)
  })
}

if (process.env.NODE_ENV !== 'production') {
  bootstrap()
}
