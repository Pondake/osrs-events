import { Injectable, OnModuleInit, OnModuleDestroy } from '@nestjs/common'
import { PrismaClient } from '../generated/prisma/index.js'
import { PrismaPg } from '@prisma/adapter-pg'

@Injectable()
export class PrismaService extends PrismaClient implements OnModuleInit, OnModuleDestroy {
  constructor() {
    // Prisma v7 uses driver adapters for runtime DB connections.
    // The datasource URL is no longer in schema.prisma — it's passed here via PrismaPg.
    const adapter = new PrismaPg({ connectionString: process.env.DATABASE_URL })
    super({ adapter })
  }

  async onModuleInit() {
    await this.$connect()
  }

  async onModuleDestroy() {
    await this.$disconnect()
  }
}
