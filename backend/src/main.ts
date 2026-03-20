import { NestFactory } from '@nestjs/core'
import { AppModule } from './app.module'

async function bootstrap() {
  const app = await NestFactory.create(AppModule)

  // Enable CORS for the Nuxt frontend (NUXT_APP_URL = http://localhost:3000)
  app.enableCors({
    origin: process.env.NUXT_APP_URL || 'http://localhost:3000',
    credentials: true,
  })

  const port = process.env.PORT || 3001
  await app.listen(port)
  console.log(`🚀 Backend running on http://localhost:${port}`)
  console.log(`📊 GraphQL Playground: http://localhost:${port}/graphql`)
}

bootstrap()
