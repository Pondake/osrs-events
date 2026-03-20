import { Injectable } from '@nestjs/common'
import { PrismaService } from '../prisma/prisma.service'
import { CreateTaskInput, UpdateTaskInput } from './dto/create-task.input'

@Injectable()
export class TasksService {
  constructor(private prisma: PrismaService) {}

  async findAll(search?: string) {
    return this.prisma.task.findMany({
      where: search
        ? {
            title: {
              contains: search,
              mode: 'insensitive'
            }
          }
        : undefined,
      orderBy: { title: 'asc' }
    })
  }

  async findById(id: string) {
    return this.prisma.task.findUnique({ where: { id } })
  }

  async create(input: CreateTaskInput) {
    return this.prisma.task.create({ data: input })
  }

  async update(id: string, input: UpdateTaskInput) {
    return this.prisma.task.update({
      where: { id },
      data: input
    })
  }

  async delete(id: string) {
    return this.prisma.task.delete({ where: { id } })
  }
}
