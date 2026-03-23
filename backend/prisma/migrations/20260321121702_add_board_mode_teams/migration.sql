/*
  Warnings:

  - A unique constraint covering the columns `[teamId,boardId]` on the table `PlayerBoard` will be added. If there are existing duplicate values, this will fail.

*/
-- CreateEnum
CREATE TYPE "BoardMode" AS ENUM ('SOLO', 'TEAM');

-- AlterTable
ALTER TABLE "Board" ADD COLUMN     "mode" "BoardMode" NOT NULL DEFAULT 'SOLO';

-- AlterTable
ALTER TABLE "PlayerBoard" ADD COLUMN     "teamId" UUID;

-- CreateTable
CREATE TABLE "BoardTeam" (
    "id" UUID NOT NULL,
    "boardId" UUID NOT NULL,
    "teamId" UUID NOT NULL,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "BoardTeam_pkey" PRIMARY KEY ("id")
);

-- CreateIndex
CREATE UNIQUE INDEX "BoardTeam_boardId_teamId_key" ON "BoardTeam"("boardId", "teamId");

-- CreateIndex
CREATE UNIQUE INDEX "PlayerBoard_teamId_boardId_key" ON "PlayerBoard"("teamId", "boardId");

-- AddForeignKey
ALTER TABLE "BoardTeam" ADD CONSTRAINT "BoardTeam_boardId_fkey" FOREIGN KEY ("boardId") REFERENCES "Board"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "BoardTeam" ADD CONSTRAINT "BoardTeam_teamId_fkey" FOREIGN KEY ("teamId") REFERENCES "Team"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "PlayerBoard" ADD CONSTRAINT "PlayerBoard_teamId_fkey" FOREIGN KEY ("teamId") REFERENCES "Team"("id") ON DELETE CASCADE ON UPDATE CASCADE;
