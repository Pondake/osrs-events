import { Resolver, Query, Mutation, Args, ID } from '@nestjs/graphql'
import { UseGuards } from '@nestjs/common'
import { TilesService } from './tiles.service'
import { TileEntity } from './entities/tile.entity'
import { UpsertTileInput } from './dto/upsert-tile.input'
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard'
import { OptionalJwtAuthGuard } from '../auth/guards/optional-jwt.guard'
import { RolesGuard } from '../auth/guards/roles.guard'
import { Roles } from '../auth/decorators/roles.decorator'

@Resolver(() => TileEntity)
export class TilesResolver {
  constructor(private tilesService: TilesService) {}

  /** Get all tiles for a board (public) */
  @UseGuards(OptionalJwtAuthGuard)
  @Query(() => [TileEntity], { name: 'tiles' })
  findByBoard(@Args('boardId', { type: () => ID }) boardId: string) {
    return this.tilesService.findByBoard(boardId)
  }

  /** Upsert a tile (admin only) */
  @UseGuards(JwtAuthGuard, RolesGuard)
  @Roles('ADMIN')
  @Mutation(() => TileEntity)
  upsertTile(@Args('input') input: UpsertTileInput) {
    return this.tilesService.upsert(input)
  }

  /** Delete a tile (admin only) */
  @UseGuards(JwtAuthGuard, RolesGuard)
  @Roles('ADMIN')
  @Mutation(() => TileEntity)
  deleteTile(@Args('id', { type: () => ID }) id: string) {
    return this.tilesService.delete(id)
  }

  /** Clear snake/ladder from a tile (admin only) */
  @UseGuards(JwtAuthGuard, RolesGuard)
  @Roles('ADMIN')
  @Mutation(() => TileEntity)
  clearSnakeLadder(@Args('id', { type: () => ID }) id: string) {
    return this.tilesService.clearSnakeLadder(id)
  }
}
