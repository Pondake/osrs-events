import 'reflect-metadata'
import { NestFactory } from '@nestjs/core'
import { AppModule } from './app.module'
import type { Request, Response } from 'express'

let nestApp: Awaited<ReturnType<typeof NestFactory.create>> | undefined

const bootstrapPromise = (async () => {
  nestApp = await NestFactory.create(AppModule, {
    logger: process.env.NODE_ENV === 'production' ? ['error', 'warn'] : undefined,
  })

  nestApp.enableCors({
    origin: true,
    methods: ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    allowedHeaders: ['Content-Type', 'Authorization', 'Accept'],
  })

  await nestApp.init()
  return nestApp
})()

// Vercel serverless handler
export default async function handler(req: Request, res: Response) {
  await bootstrapPromise
  nestApp!.getHttpAdapter().getInstance()(req, res)
}

// Local development
if (process.env.NODE_ENV !== 'production') {
  bootstrapPromise.then(async () => {
    const port = process.env.PORT || 3001
    await nestApp!.listen(port)
    console.log(`🚀 Backend running on http://localhost:${port}`)
    console.log(`📊 GraphQL Playground: http://localhost:${port}/graphql`)
  })
}
