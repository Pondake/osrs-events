import { Resolver, Query, Mutation, Args, ID } from '@nestjs/graphql'
import { UseGuards } from '@nestjs/common'
import { TasksService } from './tasks.service'
import { TaskEntity } from './entities/task.entity'
import { CreateTaskInput, UpdateTaskInput } from './dto/create-task.input'
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard'
import { RolesGuard } from '../auth/guards/roles.guard'
import { Roles } from '../auth/decorators/roles.decorator'

@Resolver(() => TaskEntity)
export class TasksResolver {
  constructor(private tasksService: TasksService) {}

  /**
   * Search/list all tasks — used for autocomplete in tile editor
   */
  @UseGuards(JwtAuthGuard)
  @Query(() => [TaskEntity], { name: 'tasks' })
  findAll(@Args('search', { nullable: true }) search?: string) {
    return this.tasksService.findAll(search)
  }

  @UseGuards(JwtAuthGuard)
  @Query(() => TaskEntity, { name: 'task', nullable: true })
  findOne(@Args('id', { type: () => ID }) id: string) {
    return this.tasksService.findById(id)
  }

  @UseGuards(JwtAuthGuard, RolesGuard)
  @Roles('ADMIN')
  @Mutation(() => TaskEntity)
  createTask(@Args('input') input: CreateTaskInput) {
    return this.tasksService.create(input)
  }

  @UseGuards(JwtAuthGuard, RolesGuard)
  @Roles('ADMIN')
  @Mutation(() => TaskEntity)
  updateTask(
    @Args('id', { type: () => ID }) id: string,
    @Args('input') input: UpdateTaskInput
  ) {
    return this.tasksService.update(id, input)
  }

  @UseGuards(JwtAuthGuard, RolesGuard)
  @Roles('ADMIN')
  @Mutation(() => TaskEntity)
  deleteTask(@Args('id', { type: () => ID }) id: string) {
    return this.tasksService.delete(id)
  }
}
