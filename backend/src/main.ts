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
  const nodeEnv = process.env.NODE_ENV || 'development'
  
  await app.listen(port)
  
  if (nodeEnv === 'development') {
    console.log(`🚀 Backend running on http://localhost:${port}`)
    console.log(`📊 GraphQL Playground: http://localhost:${port}/graphql`)
  } else {
    console.log(`Server started on port ${port}`)
  }
}

bootstrap()
