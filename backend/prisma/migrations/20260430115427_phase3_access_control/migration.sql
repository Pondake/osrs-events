-- CreateEnum
CREATE TYPE "BoardAccessMode" AS ENUM ('OPEN', 'GUILD', 'INVITE');

-- AlterTable
ALTER TABLE "Board" ADD COLUMN     "accessMode" "BoardAccessMode" NOT NULL DEFAULT 'OPEN',
ADD COLUMN     "isListed" BOOLEAN NOT NULL DEFAULT true,
ADD COLUMN     "requiredGuildId" TEXT;

-- AlterTable
ALTER TABLE "Team" ADD COLUMN     "guildId" TEXT,
ADD COLUMN     "guildName" TEXT;

-- CreateTable
CREATE TABLE "UserGuild" (
    "id" UUID NOT NULL,
    "userId" UUID NOT NULL,
    "guildId" TEXT NOT NULL,
    "guildName" TEXT NOT NULL,
    "guildIcon" TEXT,
    "syncedAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "UserGuild_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "BoardInvite" (
    "id" UUID NOT NULL,
    "boardId" UUID NOT NULL,
    "token" TEXT NOT NULL,
    "shortCode" TEXT NOT NULL,
    "label" TEXT,
    "createdBy" UUID NOT NULL,
    "expiresAt" TIMESTAMP(3),
    "maxUses" INTEGER,
    "useCount" INTEGER NOT NULL DEFAULT 0,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "BoardInvite_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "BoardAccess" (
    "id" UUID NOT NULL,
    "boardId" UUID NOT NULL,
    "userId" UUID NOT NULL,
    "inviteId" UUID,
    "accessMode" "BoardAccessMode" NOT NULL,
    "joinedAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "BoardAccess_pkey" PRIMARY KEY ("id")
);

-- CreateIndex
CREATE INDEX "UserGuild_userId_idx" ON "UserGuild"("userId");

-- CreateIndex
CREATE UNIQUE INDEX "UserGuild_userId_guildId_key" ON "UserGuild"("userId", "guildId");

-- CreateIndex
CREATE UNIQUE INDEX "BoardInvite_token_key" ON "BoardInvite"("token");

-- CreateIndex
CREATE UNIQUE INDEX "BoardInvite_boardId_shortCode_key" ON "BoardInvite"("boardId", "shortCode");

-- CreateIndex
CREATE UNIQUE INDEX "BoardAccess_boardId_userId_key" ON "BoardAccess"("boardId", "userId");

-- AddForeignKey
ALTER TABLE "UserGuild" ADD CONSTRAINT "UserGuild_userId_fkey" FOREIGN KEY ("userId") REFERENCES "User"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "BoardInvite" ADD CONSTRAINT "BoardInvite_boardId_fkey" FOREIGN KEY ("boardId") REFERENCES "Board"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "BoardAccess" ADD CONSTRAINT "BoardAccess_boardId_fkey" FOREIGN KEY ("boardId") REFERENCES "Board"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "BoardAccess" ADD CONSTRAINT "BoardAccess_userId_fkey" FOREIGN KEY ("userId") REFERENCES "User"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "BoardAccess" ADD CONSTRAINT "BoardAccess_inviteId_fkey" FOREIGN KEY ("inviteId") REFERENCES "BoardInvite"("id") ON DELETE SET NULL ON UPDATE CASCADE;
