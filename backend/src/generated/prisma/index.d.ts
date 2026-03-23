
/**
 * Client
**/

import * as runtime from './runtime/client.js';
import $Types = runtime.Types // general types
import $Public = runtime.Types.Public
import $Utils = runtime.Types.Utils
import $Extensions = runtime.Types.Extensions
import $Result = runtime.Types.Result

export type PrismaPromise<T> = $Public.PrismaPromise<T>


/**
 * Model Role
 * 
 */
export type Role = $Result.DefaultSelection<Prisma.$RolePayload>
/**
 * Model User
 * 
 */
export type User = $Result.DefaultSelection<Prisma.$UserPayload>
/**
 * Model UserPermission
 * 
 */
export type UserPermission = $Result.DefaultSelection<Prisma.$UserPermissionPayload>
/**
 * Model UserRole
 * 
 */
export type UserRole = $Result.DefaultSelection<Prisma.$UserRolePayload>
/**
 * Model Task
 * 
 */
export type Task = $Result.DefaultSelection<Prisma.$TaskPayload>
/**
 * Model Board
 * 
 */
export type Board = $Result.DefaultSelection<Prisma.$BoardPayload>
/**
 * Model BoardTeam
 * 
 */
export type BoardTeam = $Result.DefaultSelection<Prisma.$BoardTeamPayload>
/**
 * Model BoardAuthor
 * 
 */
export type BoardAuthor = $Result.DefaultSelection<Prisma.$BoardAuthorPayload>
/**
 * Model Tile
 * 
 */
export type Tile = $Result.DefaultSelection<Prisma.$TilePayload>
/**
 * Model PlayerBoard
 * 
 */
export type PlayerBoard = $Result.DefaultSelection<Prisma.$PlayerBoardPayload>
/**
 * Model CompletedTile
 * 
 */
export type CompletedTile = $Result.DefaultSelection<Prisma.$CompletedTilePayload>
/**
 * Model Team
 * 
 */
export type Team = $Result.DefaultSelection<Prisma.$TeamPayload>
/**
 * Model TeamMember
 * 
 */
export type TeamMember = $Result.DefaultSelection<Prisma.$TeamMemberPayload>

/**
 * Enums
 */
export namespace $Enums {
  export const BoardSize: {
  SIZE_5X5: 'SIZE_5X5',
  SIZE_7X7: 'SIZE_7X7',
  SIZE_9X9: 'SIZE_9X9'
};

export type BoardSize = (typeof BoardSize)[keyof typeof BoardSize]


export const BoardMode: {
  SOLO: 'SOLO',
  TEAM: 'TEAM'
};

export type BoardMode = (typeof BoardMode)[keyof typeof BoardMode]


export const TileType: {
  NORMAL: 'NORMAL',
  SNAKE: 'SNAKE',
  LADDER: 'LADDER'
};

export type TileType = (typeof TileType)[keyof typeof TileType]


export const CompletionSource: {
  MANUAL: 'MANUAL',
  RUNELITE: 'RUNELITE'
};

export type CompletionSource = (typeof CompletionSource)[keyof typeof CompletionSource]

}

export type BoardSize = $Enums.BoardSize

export const BoardSize: typeof $Enums.BoardSize

export type BoardMode = $Enums.BoardMode

export const BoardMode: typeof $Enums.BoardMode

export type TileType = $Enums.TileType

export const TileType: typeof $Enums.TileType

export type CompletionSource = $Enums.CompletionSource

export const CompletionSource: typeof $Enums.CompletionSource

/**
 * ##  Prisma Client ʲˢ
 *
 * Type-safe database client for TypeScript & Node.js
 * @example
 * ```
 * const prisma = new PrismaClient({
 *   adapter: new PrismaPg({ connectionString: process.env.DATABASE_URL })
 * })
 * // Fetch zero or more Roles
 * const roles = await prisma.role.findMany()
 * ```
 *
 *
 * Read more in our [docs](https://pris.ly/d/client).
 */
export class PrismaClient<
  ClientOptions extends Prisma.PrismaClientOptions = Prisma.PrismaClientOptions,
  const U = 'log' extends keyof ClientOptions ? ClientOptions['log'] extends Array<Prisma.LogLevel | Prisma.LogDefinition> ? Prisma.GetEvents<ClientOptions['log']> : never : never,
  ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs
> {
  [K: symbol]: { types: Prisma.TypeMap<ExtArgs>['other'] }

    /**
   * ##  Prisma Client ʲˢ
   *
   * Type-safe database client for TypeScript & Node.js
   * @example
   * ```
   * const prisma = new PrismaClient({
   *   adapter: new PrismaPg({ connectionString: process.env.DATABASE_URL })
   * })
   * // Fetch zero or more Roles
   * const roles = await prisma.role.findMany()
   * ```
   *
   *
   * Read more in our [docs](https://pris.ly/d/client).
   */

  constructor(optionsArg ?: Prisma.Subset<ClientOptions, Prisma.PrismaClientOptions>);
  $on<V extends U>(eventType: V, callback: (event: V extends 'query' ? Prisma.QueryEvent : Prisma.LogEvent) => void): PrismaClient;

  /**
   * Connect with the database
   */
  $connect(): $Utils.JsPromise<void>;

  /**
   * Disconnect from the database
   */
  $disconnect(): $Utils.JsPromise<void>;

/**
   * Executes a prepared raw query and returns the number of affected rows.
   * @example
   * ```
   * const result = await prisma.$executeRaw`UPDATE User SET cool = ${true} WHERE email = ${'user@email.com'};`
   * ```
   *
   * Read more in our [docs](https://pris.ly/d/raw-queries).
   */
  $executeRaw<T = unknown>(query: TemplateStringsArray | Prisma.Sql, ...values: any[]): Prisma.PrismaPromise<number>;

  /**
   * Executes a raw query and returns the number of affected rows.
   * Susceptible to SQL injections, see documentation.
   * @example
   * ```
   * const result = await prisma.$executeRawUnsafe('UPDATE User SET cool = $1 WHERE email = $2 ;', true, 'user@email.com')
   * ```
   *
   * Read more in our [docs](https://pris.ly/d/raw-queries).
   */
  $executeRawUnsafe<T = unknown>(query: string, ...values: any[]): Prisma.PrismaPromise<number>;

  /**
   * Performs a prepared raw query and returns the `SELECT` data.
   * @example
   * ```
   * const result = await prisma.$queryRaw`SELECT * FROM User WHERE id = ${1} OR email = ${'user@email.com'};`
   * ```
   *
   * Read more in our [docs](https://pris.ly/d/raw-queries).
   */
  $queryRaw<T = unknown>(query: TemplateStringsArray | Prisma.Sql, ...values: any[]): Prisma.PrismaPromise<T>;

  /**
   * Performs a raw query and returns the `SELECT` data.
   * Susceptible to SQL injections, see documentation.
   * @example
   * ```
   * const result = await prisma.$queryRawUnsafe('SELECT * FROM User WHERE id = $1 OR email = $2;', 1, 'user@email.com')
   * ```
   *
   * Read more in our [docs](https://pris.ly/d/raw-queries).
   */
  $queryRawUnsafe<T = unknown>(query: string, ...values: any[]): Prisma.PrismaPromise<T>;


  /**
   * Allows the running of a sequence of read/write operations that are guaranteed to either succeed or fail as a whole.
   * @example
   * ```
   * const [george, bob, alice] = await prisma.$transaction([
   *   prisma.user.create({ data: { name: 'George' } }),
   *   prisma.user.create({ data: { name: 'Bob' } }),
   *   prisma.user.create({ data: { name: 'Alice' } }),
   * ])
   * ```
   * 
   * Read more in our [docs](https://www.prisma.io/docs/orm/prisma-client/queries/transactions).
   */
  $transaction<P extends Prisma.PrismaPromise<any>[]>(arg: [...P], options?: { isolationLevel?: Prisma.TransactionIsolationLevel }): $Utils.JsPromise<runtime.Types.Utils.UnwrapTuple<P>>

  $transaction<R>(fn: (prisma: Omit<PrismaClient, runtime.ITXClientDenyList>) => $Utils.JsPromise<R>, options?: { maxWait?: number, timeout?: number, isolationLevel?: Prisma.TransactionIsolationLevel }): $Utils.JsPromise<R>

  $extends: $Extensions.ExtendsHook<"extends", Prisma.TypeMapCb<ClientOptions>, ExtArgs, $Utils.Call<Prisma.TypeMapCb<ClientOptions>, {
    extArgs: ExtArgs
  }>>

      /**
   * `prisma.role`: Exposes CRUD operations for the **Role** model.
    * Example usage:
    * ```ts
    * // Fetch zero or more Roles
    * const roles = await prisma.role.findMany()
    * ```
    */
  get role(): Prisma.RoleDelegate<ExtArgs, ClientOptions>;

  /**
   * `prisma.user`: Exposes CRUD operations for the **User** model.
    * Example usage:
    * ```ts
    * // Fetch zero or more Users
    * const users = await prisma.user.findMany()
    * ```
    */
  get user(): Prisma.UserDelegate<ExtArgs, ClientOptions>;

  /**
   * `prisma.userPermission`: Exposes CRUD operations for the **UserPermission** model.
    * Example usage:
    * ```ts
    * // Fetch zero or more UserPermissions
    * const userPermissions = await prisma.userPermission.findMany()
    * ```
    */
  get userPermission(): Prisma.UserPermissionDelegate<ExtArgs, ClientOptions>;

  /**
   * `prisma.userRole`: Exposes CRUD operations for the **UserRole** model.
    * Example usage:
    * ```ts
    * // Fetch zero or more UserRoles
    * const userRoles = await prisma.userRole.findMany()
    * ```
    */
  get userRole(): Prisma.UserRoleDelegate<ExtArgs, ClientOptions>;

  /**
   * `prisma.task`: Exposes CRUD operations for the **Task** model.
    * Example usage:
    * ```ts
    * // Fetch zero or more Tasks
    * const tasks = await prisma.task.findMany()
    * ```
    */
  get task(): Prisma.TaskDelegate<ExtArgs, ClientOptions>;

  /**
   * `prisma.board`: Exposes CRUD operations for the **Board** model.
    * Example usage:
    * ```ts
    * // Fetch zero or more Boards
    * const boards = await prisma.board.findMany()
    * ```
    */
  get board(): Prisma.BoardDelegate<ExtArgs, ClientOptions>;

  /**
   * `prisma.boardTeam`: Exposes CRUD operations for the **BoardTeam** model.
    * Example usage:
    * ```ts
    * // Fetch zero or more BoardTeams
    * const boardTeams = await prisma.boardTeam.findMany()
    * ```
    */
  get boardTeam(): Prisma.BoardTeamDelegate<ExtArgs, ClientOptions>;

  /**
   * `prisma.boardAuthor`: Exposes CRUD operations for the **BoardAuthor** model.
    * Example usage:
    * ```ts
    * // Fetch zero or more BoardAuthors
    * const boardAuthors = await prisma.boardAuthor.findMany()
    * ```
    */
  get boardAuthor(): Prisma.BoardAuthorDelegate<ExtArgs, ClientOptions>;

  /**
   * `prisma.tile`: Exposes CRUD operations for the **Tile** model.
    * Example usage:
    * ```ts
    * // Fetch zero or more Tiles
    * const tiles = await prisma.tile.findMany()
    * ```
    */
  get tile(): Prisma.TileDelegate<ExtArgs, ClientOptions>;

  /**
   * `prisma.playerBoard`: Exposes CRUD operations for the **PlayerBoard** model.
    * Example usage:
    * ```ts
    * // Fetch zero or more PlayerBoards
    * const playerBoards = await prisma.playerBoard.findMany()
    * ```
    */
  get playerBoard(): Prisma.PlayerBoardDelegate<ExtArgs, ClientOptions>;

  /**
   * `prisma.completedTile`: Exposes CRUD operations for the **CompletedTile** model.
    * Example usage:
    * ```ts
    * // Fetch zero or more CompletedTiles
    * const completedTiles = await prisma.completedTile.findMany()
    * ```
    */
  get completedTile(): Prisma.CompletedTileDelegate<ExtArgs, ClientOptions>;

  /**
   * `prisma.team`: Exposes CRUD operations for the **Team** model.
    * Example usage:
    * ```ts
    * // Fetch zero or more Teams
    * const teams = await prisma.team.findMany()
    * ```
    */
  get team(): Prisma.TeamDelegate<ExtArgs, ClientOptions>;

  /**
   * `prisma.teamMember`: Exposes CRUD operations for the **TeamMember** model.
    * Example usage:
    * ```ts
    * // Fetch zero or more TeamMembers
    * const teamMembers = await prisma.teamMember.findMany()
    * ```
    */
  get teamMember(): Prisma.TeamMemberDelegate<ExtArgs, ClientOptions>;
}

export namespace Prisma {
  export import DMMF = runtime.DMMF

  export type PrismaPromise<T> = $Public.PrismaPromise<T>

  /**
   * Validator
   */
  export import validator = runtime.Public.validator

  /**
   * Prisma Errors
   */
  export import PrismaClientKnownRequestError = runtime.PrismaClientKnownRequestError
  export import PrismaClientUnknownRequestError = runtime.PrismaClientUnknownRequestError
  export import PrismaClientRustPanicError = runtime.PrismaClientRustPanicError
  export import PrismaClientInitializationError = runtime.PrismaClientInitializationError
  export import PrismaClientValidationError = runtime.PrismaClientValidationError

  /**
   * Re-export of sql-template-tag
   */
  export import sql = runtime.sqltag
  export import empty = runtime.empty
  export import join = runtime.join
  export import raw = runtime.raw
  export import Sql = runtime.Sql



  /**
   * Decimal.js
   */
  export import Decimal = runtime.Decimal

  export type DecimalJsLike = runtime.DecimalJsLike

  /**
  * Extensions
  */
  export import Extension = $Extensions.UserArgs
  export import getExtensionContext = runtime.Extensions.getExtensionContext
  export import Args = $Public.Args
  export import Payload = $Public.Payload
  export import Result = $Public.Result
  export import Exact = $Public.Exact

  /**
   * Prisma Client JS version: 7.5.0
   * Query Engine version: 280c870be64f457428992c43c1f6d557fab6e29e
   */
  export type PrismaVersion = {
    client: string
    engine: string
  }

  export const prismaVersion: PrismaVersion

  /**
   * Utility Types
   */


  export import Bytes = runtime.Bytes
  export import JsonObject = runtime.JsonObject
  export import JsonArray = runtime.JsonArray
  export import JsonValue = runtime.JsonValue
  export import InputJsonObject = runtime.InputJsonObject
  export import InputJsonArray = runtime.InputJsonArray
  export import InputJsonValue = runtime.InputJsonValue

  /**
   * Types of the values used to represent different kinds of `null` values when working with JSON fields.
   *
   * @see https://www.prisma.io/docs/concepts/components/prisma-client/working-with-fields/working-with-json-fields#filtering-on-a-json-field
   */
  namespace NullTypes {
    /**
    * Type of `Prisma.DbNull`.
    *
    * You cannot use other instances of this class. Please use the `Prisma.DbNull` value.
    *
    * @see https://www.prisma.io/docs/concepts/components/prisma-client/working-with-fields/working-with-json-fields#filtering-on-a-json-field
    */
    class DbNull {
      private DbNull: never
      private constructor()
    }

    /**
    * Type of `Prisma.JsonNull`.
    *
    * You cannot use other instances of this class. Please use the `Prisma.JsonNull` value.
    *
    * @see https://www.prisma.io/docs/concepts/components/prisma-client/working-with-fields/working-with-json-fields#filtering-on-a-json-field
    */
    class JsonNull {
      private JsonNull: never
      private constructor()
    }

    /**
    * Type of `Prisma.AnyNull`.
    *
    * You cannot use other instances of this class. Please use the `Prisma.AnyNull` value.
    *
    * @see https://www.prisma.io/docs/concepts/components/prisma-client/working-with-fields/working-with-json-fields#filtering-on-a-json-field
    */
    class AnyNull {
      private AnyNull: never
      private constructor()
    }
  }

  /**
   * Helper for filtering JSON entries that have `null` on the database (empty on the db)
   *
   * @see https://www.prisma.io/docs/concepts/components/prisma-client/working-with-fields/working-with-json-fields#filtering-on-a-json-field
   */
  export const DbNull: NullTypes.DbNull

  /**
   * Helper for filtering JSON entries that have JSON `null` values (not empty on the db)
   *
   * @see https://www.prisma.io/docs/concepts/components/prisma-client/working-with-fields/working-with-json-fields#filtering-on-a-json-field
   */
  export const JsonNull: NullTypes.JsonNull

  /**
   * Helper for filtering JSON entries that are `Prisma.DbNull` or `Prisma.JsonNull`
   *
   * @see https://www.prisma.io/docs/concepts/components/prisma-client/working-with-fields/working-with-json-fields#filtering-on-a-json-field
   */
  export const AnyNull: NullTypes.AnyNull

  type SelectAndInclude = {
    select: any
    include: any
  }

  type SelectAndOmit = {
    select: any
    omit: any
  }

  /**
   * Get the type of the value, that the Promise holds.
   */
  export type PromiseType<T extends PromiseLike<any>> = T extends PromiseLike<infer U> ? U : T;

  /**
   * Get the return type of a function which returns a Promise.
   */
  export type PromiseReturnType<T extends (...args: any) => $Utils.JsPromise<any>> = PromiseType<ReturnType<T>>

  /**
   * From T, pick a set of properties whose keys are in the union K
   */
  type Prisma__Pick<T, K extends keyof T> = {
      [P in K]: T[P];
  };


  export type Enumerable<T> = T | Array<T>;

  export type RequiredKeys<T> = {
    [K in keyof T]-?: {} extends Prisma__Pick<T, K> ? never : K
  }[keyof T]

  export type TruthyKeys<T> = keyof {
    [K in keyof T as T[K] extends false | undefined | null ? never : K]: K
  }

  export type TrueKeys<T> = TruthyKeys<Prisma__Pick<T, RequiredKeys<T>>>

  /**
   * Subset
   * @desc From `T` pick properties that exist in `U`. Simple version of Intersection
   */
  export type Subset<T, U> = {
    [key in keyof T]: key extends keyof U ? T[key] : never;
  };

  /**
   * SelectSubset
   * @desc From `T` pick properties that exist in `U`. Simple version of Intersection.
   * Additionally, it validates, if both select and include are present. If the case, it errors.
   */
  export type SelectSubset<T, U> = {
    [key in keyof T]: key extends keyof U ? T[key] : never
  } &
    (T extends SelectAndInclude
      ? 'Please either choose `select` or `include`.'
      : T extends SelectAndOmit
        ? 'Please either choose `select` or `omit`.'
        : {})

  /**
   * Subset + Intersection
   * @desc From `T` pick properties that exist in `U` and intersect `K`
   */
  export type SubsetIntersection<T, U, K> = {
    [key in keyof T]: key extends keyof U ? T[key] : never
  } &
    K

  type Without<T, U> = { [P in Exclude<keyof T, keyof U>]?: never };

  /**
   * XOR is needed to have a real mutually exclusive union type
   * https://stackoverflow.com/questions/42123407/does-typescript-support-mutually-exclusive-types
   */
  type XOR<T, U> =
    T extends object ?
    U extends object ?
      (Without<T, U> & U) | (Without<U, T> & T)
    : U : T


  /**
   * Is T a Record?
   */
  type IsObject<T extends any> = T extends Array<any>
  ? False
  : T extends Date
  ? False
  : T extends Uint8Array
  ? False
  : T extends BigInt
  ? False
  : T extends object
  ? True
  : False


  /**
   * If it's T[], return T
   */
  export type UnEnumerate<T extends unknown> = T extends Array<infer U> ? U : T

  /**
   * From ts-toolbelt
   */

  type __Either<O extends object, K extends Key> = Omit<O, K> &
    {
      // Merge all but K
      [P in K]: Prisma__Pick<O, P & keyof O> // With K possibilities
    }[K]

  type EitherStrict<O extends object, K extends Key> = Strict<__Either<O, K>>

  type EitherLoose<O extends object, K extends Key> = ComputeRaw<__Either<O, K>>

  type _Either<
    O extends object,
    K extends Key,
    strict extends Boolean
  > = {
    1: EitherStrict<O, K>
    0: EitherLoose<O, K>
  }[strict]

  type Either<
    O extends object,
    K extends Key,
    strict extends Boolean = 1
  > = O extends unknown ? _Either<O, K, strict> : never

  export type Union = any

  type PatchUndefined<O extends object, O1 extends object> = {
    [K in keyof O]: O[K] extends undefined ? At<O1, K> : O[K]
  } & {}

  /** Helper Types for "Merge" **/
  export type IntersectOf<U extends Union> = (
    U extends unknown ? (k: U) => void : never
  ) extends (k: infer I) => void
    ? I
    : never

  export type Overwrite<O extends object, O1 extends object> = {
      [K in keyof O]: K extends keyof O1 ? O1[K] : O[K];
  } & {};

  type _Merge<U extends object> = IntersectOf<Overwrite<U, {
      [K in keyof U]-?: At<U, K>;
  }>>;

  type Key = string | number | symbol;
  type AtBasic<O extends object, K extends Key> = K extends keyof O ? O[K] : never;
  type AtStrict<O extends object, K extends Key> = O[K & keyof O];
  type AtLoose<O extends object, K extends Key> = O extends unknown ? AtStrict<O, K> : never;
  export type At<O extends object, K extends Key, strict extends Boolean = 1> = {
      1: AtStrict<O, K>;
      0: AtLoose<O, K>;
  }[strict];

  export type ComputeRaw<A extends any> = A extends Function ? A : {
    [K in keyof A]: A[K];
  } & {};

  export type OptionalFlat<O> = {
    [K in keyof O]?: O[K];
  } & {};

  type _Record<K extends keyof any, T> = {
    [P in K]: T;
  };

  // cause typescript not to expand types and preserve names
  type NoExpand<T> = T extends unknown ? T : never;

  // this type assumes the passed object is entirely optional
  type AtLeast<O extends object, K extends string> = NoExpand<
    O extends unknown
    ? | (K extends keyof O ? { [P in K]: O[P] } & O : O)
      | {[P in keyof O as P extends K ? P : never]-?: O[P]} & O
    : never>;

  type _Strict<U, _U = U> = U extends unknown ? U & OptionalFlat<_Record<Exclude<Keys<_U>, keyof U>, never>> : never;

  export type Strict<U extends object> = ComputeRaw<_Strict<U>>;
  /** End Helper Types for "Merge" **/

  export type Merge<U extends object> = ComputeRaw<_Merge<Strict<U>>>;

  /**
  A [[Boolean]]
  */
  export type Boolean = True | False

  // /**
  // 1
  // */
  export type True = 1

  /**
  0
  */
  export type False = 0

  export type Not<B extends Boolean> = {
    0: 1
    1: 0
  }[B]

  export type Extends<A1 extends any, A2 extends any> = [A1] extends [never]
    ? 0 // anything `never` is false
    : A1 extends A2
    ? 1
    : 0

  export type Has<U extends Union, U1 extends Union> = Not<
    Extends<Exclude<U1, U>, U1>
  >

  export type Or<B1 extends Boolean, B2 extends Boolean> = {
    0: {
      0: 0
      1: 1
    }
    1: {
      0: 1
      1: 1
    }
  }[B1][B2]

  export type Keys<U extends Union> = U extends unknown ? keyof U : never

  type Cast<A, B> = A extends B ? A : B;

  export const type: unique symbol;



  /**
   * Used by group by
   */

  export type GetScalarType<T, O> = O extends object ? {
    [P in keyof T]: P extends keyof O
      ? O[P]
      : never
  } : never

  type FieldPaths<
    T,
    U = Omit<T, '_avg' | '_sum' | '_count' | '_min' | '_max'>
  > = IsObject<T> extends True ? U : T

  type GetHavingFields<T> = {
    [K in keyof T]: Or<
      Or<Extends<'OR', K>, Extends<'AND', K>>,
      Extends<'NOT', K>
    > extends True
      ? // infer is only needed to not hit TS limit
        // based on the brilliant idea of Pierre-Antoine Mills
        // https://github.com/microsoft/TypeScript/issues/30188#issuecomment-478938437
        T[K] extends infer TK
        ? GetHavingFields<UnEnumerate<TK> extends object ? Merge<UnEnumerate<TK>> : never>
        : never
      : {} extends FieldPaths<T[K]>
      ? never
      : K
  }[keyof T]

  /**
   * Convert tuple to union
   */
  type _TupleToUnion<T> = T extends (infer E)[] ? E : never
  type TupleToUnion<K extends readonly any[]> = _TupleToUnion<K>
  type MaybeTupleToUnion<T> = T extends any[] ? TupleToUnion<T> : T

  /**
   * Like `Pick`, but additionally can also accept an array of keys
   */
  type PickEnumerable<T, K extends Enumerable<keyof T> | keyof T> = Prisma__Pick<T, MaybeTupleToUnion<K>>

  /**
   * Exclude all keys with underscores
   */
  type ExcludeUnderscoreKeys<T extends string> = T extends `_${string}` ? never : T


  export type FieldRef<Model, FieldType> = runtime.FieldRef<Model, FieldType>

  type FieldRefInputType<Model, FieldType> = Model extends never ? never : FieldRef<Model, FieldType>


  export const ModelName: {
    Role: 'Role',
    User: 'User',
    UserPermission: 'UserPermission',
    UserRole: 'UserRole',
    Task: 'Task',
    Board: 'Board',
    BoardTeam: 'BoardTeam',
    BoardAuthor: 'BoardAuthor',
    Tile: 'Tile',
    PlayerBoard: 'PlayerBoard',
    CompletedTile: 'CompletedTile',
    Team: 'Team',
    TeamMember: 'TeamMember'
  };

  export type ModelName = (typeof ModelName)[keyof typeof ModelName]



  interface TypeMapCb<ClientOptions = {}> extends $Utils.Fn<{extArgs: $Extensions.InternalArgs }, $Utils.Record<string, any>> {
    returns: Prisma.TypeMap<this['params']['extArgs'], ClientOptions extends { omit: infer OmitOptions } ? OmitOptions : {}>
  }

  export type TypeMap<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs, GlobalOmitOptions = {}> = {
    globalOmitOptions: {
      omit: GlobalOmitOptions
    }
    meta: {
      modelProps: "role" | "user" | "userPermission" | "userRole" | "task" | "board" | "boardTeam" | "boardAuthor" | "tile" | "playerBoard" | "completedTile" | "team" | "teamMember"
      txIsolationLevel: Prisma.TransactionIsolationLevel
    }
    model: {
      Role: {
        payload: Prisma.$RolePayload<ExtArgs>
        fields: Prisma.RoleFieldRefs
        operations: {
          findUnique: {
            args: Prisma.RoleFindUniqueArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$RolePayload> | null
          }
          findUniqueOrThrow: {
            args: Prisma.RoleFindUniqueOrThrowArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$RolePayload>
          }
          findFirst: {
            args: Prisma.RoleFindFirstArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$RolePayload> | null
          }
          findFirstOrThrow: {
            args: Prisma.RoleFindFirstOrThrowArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$RolePayload>
          }
          findMany: {
            args: Prisma.RoleFindManyArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$RolePayload>[]
          }
          create: {
            args: Prisma.RoleCreateArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$RolePayload>
          }
          createMany: {
            args: Prisma.RoleCreateManyArgs<ExtArgs>
            result: BatchPayload
          }
          createManyAndReturn: {
            args: Prisma.RoleCreateManyAndReturnArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$RolePayload>[]
          }
          delete: {
            args: Prisma.RoleDeleteArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$RolePayload>
          }
          update: {
            args: Prisma.RoleUpdateArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$RolePayload>
          }
          deleteMany: {
            args: Prisma.RoleDeleteManyArgs<ExtArgs>
            result: BatchPayload
          }
          updateMany: {
            args: Prisma.RoleUpdateManyArgs<ExtArgs>
            result: BatchPayload
          }
          updateManyAndReturn: {
            args: Prisma.RoleUpdateManyAndReturnArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$RolePayload>[]
          }
          upsert: {
            args: Prisma.RoleUpsertArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$RolePayload>
          }
          aggregate: {
            args: Prisma.RoleAggregateArgs<ExtArgs>
            result: $Utils.Optional<AggregateRole>
          }
          groupBy: {
            args: Prisma.RoleGroupByArgs<ExtArgs>
            result: $Utils.Optional<RoleGroupByOutputType>[]
          }
          count: {
            args: Prisma.RoleCountArgs<ExtArgs>
            result: $Utils.Optional<RoleCountAggregateOutputType> | number
          }
        }
      }
      User: {
        payload: Prisma.$UserPayload<ExtArgs>
        fields: Prisma.UserFieldRefs
        operations: {
          findUnique: {
            args: Prisma.UserFindUniqueArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$UserPayload> | null
          }
          findUniqueOrThrow: {
            args: Prisma.UserFindUniqueOrThrowArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$UserPayload>
          }
          findFirst: {
            args: Prisma.UserFindFirstArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$UserPayload> | null
          }
          findFirstOrThrow: {
            args: Prisma.UserFindFirstOrThrowArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$UserPayload>
          }
          findMany: {
            args: Prisma.UserFindManyArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$UserPayload>[]
          }
          create: {
            args: Prisma.UserCreateArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$UserPayload>
          }
          createMany: {
            args: Prisma.UserCreateManyArgs<ExtArgs>
            result: BatchPayload
          }
          createManyAndReturn: {
            args: Prisma.UserCreateManyAndReturnArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$UserPayload>[]
          }
          delete: {
            args: Prisma.UserDeleteArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$UserPayload>
          }
          update: {
            args: Prisma.UserUpdateArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$UserPayload>
          }
          deleteMany: {
            args: Prisma.UserDeleteManyArgs<ExtArgs>
            result: BatchPayload
          }
          updateMany: {
            args: Prisma.UserUpdateManyArgs<ExtArgs>
            result: BatchPayload
          }
          updateManyAndReturn: {
            args: Prisma.UserUpdateManyAndReturnArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$UserPayload>[]
          }
          upsert: {
            args: Prisma.UserUpsertArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$UserPayload>
          }
          aggregate: {
            args: Prisma.UserAggregateArgs<ExtArgs>
            result: $Utils.Optional<AggregateUser>
          }
          groupBy: {
            args: Prisma.UserGroupByArgs<ExtArgs>
            result: $Utils.Optional<UserGroupByOutputType>[]
          }
          count: {
            args: Prisma.UserCountArgs<ExtArgs>
            result: $Utils.Optional<UserCountAggregateOutputType> | number
          }
        }
      }
      UserPermission: {
        payload: Prisma.$UserPermissionPayload<ExtArgs>
        fields: Prisma.UserPermissionFieldRefs
        operations: {
          findUnique: {
            args: Prisma.UserPermissionFindUniqueArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$UserPermissionPayload> | null
          }
          findUniqueOrThrow: {
            args: Prisma.UserPermissionFindUniqueOrThrowArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$UserPermissionPayload>
          }
          findFirst: {
            args: Prisma.UserPermissionFindFirstArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$UserPermissionPayload> | null
          }
          findFirstOrThrow: {
            args: Prisma.UserPermissionFindFirstOrThrowArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$UserPermissionPayload>
          }
          findMany: {
            args: Prisma.UserPermissionFindManyArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$UserPermissionPayload>[]
          }
          create: {
            args: Prisma.UserPermissionCreateArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$UserPermissionPayload>
          }
          createMany: {
            args: Prisma.UserPermissionCreateManyArgs<ExtArgs>
            result: BatchPayload
          }
          createManyAndReturn: {
            args: Prisma.UserPermissionCreateManyAndReturnArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$UserPermissionPayload>[]
          }
          delete: {
            args: Prisma.UserPermissionDeleteArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$UserPermissionPayload>
          }
          update: {
            args: Prisma.UserPermissionUpdateArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$UserPermissionPayload>
          }
          deleteMany: {
            args: Prisma.UserPermissionDeleteManyArgs<ExtArgs>
            result: BatchPayload
          }
          updateMany: {
            args: Prisma.UserPermissionUpdateManyArgs<ExtArgs>
            result: BatchPayload
          }
          updateManyAndReturn: {
            args: Prisma.UserPermissionUpdateManyAndReturnArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$UserPermissionPayload>[]
          }
          upsert: {
            args: Prisma.UserPermissionUpsertArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$UserPermissionPayload>
          }
          aggregate: {
            args: Prisma.UserPermissionAggregateArgs<ExtArgs>
            result: $Utils.Optional<AggregateUserPermission>
          }
          groupBy: {
            args: Prisma.UserPermissionGroupByArgs<ExtArgs>
            result: $Utils.Optional<UserPermissionGroupByOutputType>[]
          }
          count: {
            args: Prisma.UserPermissionCountArgs<ExtArgs>
            result: $Utils.Optional<UserPermissionCountAggregateOutputType> | number
          }
        }
      }
      UserRole: {
        payload: Prisma.$UserRolePayload<ExtArgs>
        fields: Prisma.UserRoleFieldRefs
        operations: {
          findUnique: {
            args: Prisma.UserRoleFindUniqueArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$UserRolePayload> | null
          }
          findUniqueOrThrow: {
            args: Prisma.UserRoleFindUniqueOrThrowArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$UserRolePayload>
          }
          findFirst: {
            args: Prisma.UserRoleFindFirstArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$UserRolePayload> | null
          }
          findFirstOrThrow: {
            args: Prisma.UserRoleFindFirstOrThrowArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$UserRolePayload>
          }
          findMany: {
            args: Prisma.UserRoleFindManyArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$UserRolePayload>[]
          }
          create: {
            args: Prisma.UserRoleCreateArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$UserRolePayload>
          }
          createMany: {
            args: Prisma.UserRoleCreateManyArgs<ExtArgs>
            result: BatchPayload
          }
          createManyAndReturn: {
            args: Prisma.UserRoleCreateManyAndReturnArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$UserRolePayload>[]
          }
          delete: {
            args: Prisma.UserRoleDeleteArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$UserRolePayload>
          }
          update: {
            args: Prisma.UserRoleUpdateArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$UserRolePayload>
          }
          deleteMany: {
            args: Prisma.UserRoleDeleteManyArgs<ExtArgs>
            result: BatchPayload
          }
          updateMany: {
            args: Prisma.UserRoleUpdateManyArgs<ExtArgs>
            result: BatchPayload
          }
          updateManyAndReturn: {
            args: Prisma.UserRoleUpdateManyAndReturnArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$UserRolePayload>[]
          }
          upsert: {
            args: Prisma.UserRoleUpsertArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$UserRolePayload>
          }
          aggregate: {
            args: Prisma.UserRoleAggregateArgs<ExtArgs>
            result: $Utils.Optional<AggregateUserRole>
          }
          groupBy: {
            args: Prisma.UserRoleGroupByArgs<ExtArgs>
            result: $Utils.Optional<UserRoleGroupByOutputType>[]
          }
          count: {
            args: Prisma.UserRoleCountArgs<ExtArgs>
            result: $Utils.Optional<UserRoleCountAggregateOutputType> | number
          }
        }
      }
      Task: {
        payload: Prisma.$TaskPayload<ExtArgs>
        fields: Prisma.TaskFieldRefs
        operations: {
          findUnique: {
            args: Prisma.TaskFindUniqueArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$TaskPayload> | null
          }
          findUniqueOrThrow: {
            args: Prisma.TaskFindUniqueOrThrowArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$TaskPayload>
          }
          findFirst: {
            args: Prisma.TaskFindFirstArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$TaskPayload> | null
          }
          findFirstOrThrow: {
            args: Prisma.TaskFindFirstOrThrowArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$TaskPayload>
          }
          findMany: {
            args: Prisma.TaskFindManyArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$TaskPayload>[]
          }
          create: {
            args: Prisma.TaskCreateArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$TaskPayload>
          }
          createMany: {
            args: Prisma.TaskCreateManyArgs<ExtArgs>
            result: BatchPayload
          }
          createManyAndReturn: {
            args: Prisma.TaskCreateManyAndReturnArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$TaskPayload>[]
          }
          delete: {
            args: Prisma.TaskDeleteArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$TaskPayload>
          }
          update: {
            args: Prisma.TaskUpdateArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$TaskPayload>
          }
          deleteMany: {
            args: Prisma.TaskDeleteManyArgs<ExtArgs>
            result: BatchPayload
          }
          updateMany: {
            args: Prisma.TaskUpdateManyArgs<ExtArgs>
            result: BatchPayload
          }
          updateManyAndReturn: {
            args: Prisma.TaskUpdateManyAndReturnArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$TaskPayload>[]
          }
          upsert: {
            args: Prisma.TaskUpsertArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$TaskPayload>
          }
          aggregate: {
            args: Prisma.TaskAggregateArgs<ExtArgs>
            result: $Utils.Optional<AggregateTask>
          }
          groupBy: {
            args: Prisma.TaskGroupByArgs<ExtArgs>
            result: $Utils.Optional<TaskGroupByOutputType>[]
          }
          count: {
            args: Prisma.TaskCountArgs<ExtArgs>
            result: $Utils.Optional<TaskCountAggregateOutputType> | number
          }
        }
      }
      Board: {
        payload: Prisma.$BoardPayload<ExtArgs>
        fields: Prisma.BoardFieldRefs
        operations: {
          findUnique: {
            args: Prisma.BoardFindUniqueArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$BoardPayload> | null
          }
          findUniqueOrThrow: {
            args: Prisma.BoardFindUniqueOrThrowArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$BoardPayload>
          }
          findFirst: {
            args: Prisma.BoardFindFirstArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$BoardPayload> | null
          }
          findFirstOrThrow: {
            args: Prisma.BoardFindFirstOrThrowArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$BoardPayload>
          }
          findMany: {
            args: Prisma.BoardFindManyArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$BoardPayload>[]
          }
          create: {
            args: Prisma.BoardCreateArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$BoardPayload>
          }
          createMany: {
            args: Prisma.BoardCreateManyArgs<ExtArgs>
            result: BatchPayload
          }
          createManyAndReturn: {
            args: Prisma.BoardCreateManyAndReturnArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$BoardPayload>[]
          }
          delete: {
            args: Prisma.BoardDeleteArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$BoardPayload>
          }
          update: {
            args: Prisma.BoardUpdateArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$BoardPayload>
          }
          deleteMany: {
            args: Prisma.BoardDeleteManyArgs<ExtArgs>
            result: BatchPayload
          }
          updateMany: {
            args: Prisma.BoardUpdateManyArgs<ExtArgs>
            result: BatchPayload
          }
          updateManyAndReturn: {
            args: Prisma.BoardUpdateManyAndReturnArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$BoardPayload>[]
          }
          upsert: {
            args: Prisma.BoardUpsertArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$BoardPayload>
          }
          aggregate: {
            args: Prisma.BoardAggregateArgs<ExtArgs>
            result: $Utils.Optional<AggregateBoard>
          }
          groupBy: {
            args: Prisma.BoardGroupByArgs<ExtArgs>
            result: $Utils.Optional<BoardGroupByOutputType>[]
          }
          count: {
            args: Prisma.BoardCountArgs<ExtArgs>
            result: $Utils.Optional<BoardCountAggregateOutputType> | number
          }
        }
      }
      BoardTeam: {
        payload: Prisma.$BoardTeamPayload<ExtArgs>
        fields: Prisma.BoardTeamFieldRefs
        operations: {
          findUnique: {
            args: Prisma.BoardTeamFindUniqueArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$BoardTeamPayload> | null
          }
          findUniqueOrThrow: {
            args: Prisma.BoardTeamFindUniqueOrThrowArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$BoardTeamPayload>
          }
          findFirst: {
            args: Prisma.BoardTeamFindFirstArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$BoardTeamPayload> | null
          }
          findFirstOrThrow: {
            args: Prisma.BoardTeamFindFirstOrThrowArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$BoardTeamPayload>
          }
          findMany: {
            args: Prisma.BoardTeamFindManyArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$BoardTeamPayload>[]
          }
          create: {
            args: Prisma.BoardTeamCreateArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$BoardTeamPayload>
          }
          createMany: {
            args: Prisma.BoardTeamCreateManyArgs<ExtArgs>
            result: BatchPayload
          }
          createManyAndReturn: {
            args: Prisma.BoardTeamCreateManyAndReturnArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$BoardTeamPayload>[]
          }
          delete: {
            args: Prisma.BoardTeamDeleteArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$BoardTeamPayload>
          }
          update: {
            args: Prisma.BoardTeamUpdateArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$BoardTeamPayload>
          }
          deleteMany: {
            args: Prisma.BoardTeamDeleteManyArgs<ExtArgs>
            result: BatchPayload
          }
          updateMany: {
            args: Prisma.BoardTeamUpdateManyArgs<ExtArgs>
            result: BatchPayload
          }
          updateManyAndReturn: {
            args: Prisma.BoardTeamUpdateManyAndReturnArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$BoardTeamPayload>[]
          }
          upsert: {
            args: Prisma.BoardTeamUpsertArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$BoardTeamPayload>
          }
          aggregate: {
            args: Prisma.BoardTeamAggregateArgs<ExtArgs>
            result: $Utils.Optional<AggregateBoardTeam>
          }
          groupBy: {
            args: Prisma.BoardTeamGroupByArgs<ExtArgs>
            result: $Utils.Optional<BoardTeamGroupByOutputType>[]
          }
          count: {
            args: Prisma.BoardTeamCountArgs<ExtArgs>
            result: $Utils.Optional<BoardTeamCountAggregateOutputType> | number
          }
        }
      }
      BoardAuthor: {
        payload: Prisma.$BoardAuthorPayload<ExtArgs>
        fields: Prisma.BoardAuthorFieldRefs
        operations: {
          findUnique: {
            args: Prisma.BoardAuthorFindUniqueArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$BoardAuthorPayload> | null
          }
          findUniqueOrThrow: {
            args: Prisma.BoardAuthorFindUniqueOrThrowArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$BoardAuthorPayload>
          }
          findFirst: {
            args: Prisma.BoardAuthorFindFirstArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$BoardAuthorPayload> | null
          }
          findFirstOrThrow: {
            args: Prisma.BoardAuthorFindFirstOrThrowArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$BoardAuthorPayload>
          }
          findMany: {
            args: Prisma.BoardAuthorFindManyArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$BoardAuthorPayload>[]
          }
          create: {
            args: Prisma.BoardAuthorCreateArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$BoardAuthorPayload>
          }
          createMany: {
            args: Prisma.BoardAuthorCreateManyArgs<ExtArgs>
            result: BatchPayload
          }
          createManyAndReturn: {
            args: Prisma.BoardAuthorCreateManyAndReturnArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$BoardAuthorPayload>[]
          }
          delete: {
            args: Prisma.BoardAuthorDeleteArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$BoardAuthorPayload>
          }
          update: {
            args: Prisma.BoardAuthorUpdateArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$BoardAuthorPayload>
          }
          deleteMany: {
            args: Prisma.BoardAuthorDeleteManyArgs<ExtArgs>
            result: BatchPayload
          }
          updateMany: {
            args: Prisma.BoardAuthorUpdateManyArgs<ExtArgs>
            result: BatchPayload
          }
          updateManyAndReturn: {
            args: Prisma.BoardAuthorUpdateManyAndReturnArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$BoardAuthorPayload>[]
          }
          upsert: {
            args: Prisma.BoardAuthorUpsertArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$BoardAuthorPayload>
          }
          aggregate: {
            args: Prisma.BoardAuthorAggregateArgs<ExtArgs>
            result: $Utils.Optional<AggregateBoardAuthor>
          }
          groupBy: {
            args: Prisma.BoardAuthorGroupByArgs<ExtArgs>
            result: $Utils.Optional<BoardAuthorGroupByOutputType>[]
          }
          count: {
            args: Prisma.BoardAuthorCountArgs<ExtArgs>
            result: $Utils.Optional<BoardAuthorCountAggregateOutputType> | number
          }
        }
      }
      Tile: {
        payload: Prisma.$TilePayload<ExtArgs>
        fields: Prisma.TileFieldRefs
        operations: {
          findUnique: {
            args: Prisma.TileFindUniqueArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$TilePayload> | null
          }
          findUniqueOrThrow: {
            args: Prisma.TileFindUniqueOrThrowArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$TilePayload>
          }
          findFirst: {
            args: Prisma.TileFindFirstArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$TilePayload> | null
          }
          findFirstOrThrow: {
            args: Prisma.TileFindFirstOrThrowArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$TilePayload>
          }
          findMany: {
            args: Prisma.TileFindManyArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$TilePayload>[]
          }
          create: {
            args: Prisma.TileCreateArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$TilePayload>
          }
          createMany: {
            args: Prisma.TileCreateManyArgs<ExtArgs>
            result: BatchPayload
          }
          createManyAndReturn: {
            args: Prisma.TileCreateManyAndReturnArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$TilePayload>[]
          }
          delete: {
            args: Prisma.TileDeleteArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$TilePayload>
          }
          update: {
            args: Prisma.TileUpdateArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$TilePayload>
          }
          deleteMany: {
            args: Prisma.TileDeleteManyArgs<ExtArgs>
            result: BatchPayload
          }
          updateMany: {
            args: Prisma.TileUpdateManyArgs<ExtArgs>
            result: BatchPayload
          }
          updateManyAndReturn: {
            args: Prisma.TileUpdateManyAndReturnArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$TilePayload>[]
          }
          upsert: {
            args: Prisma.TileUpsertArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$TilePayload>
          }
          aggregate: {
            args: Prisma.TileAggregateArgs<ExtArgs>
            result: $Utils.Optional<AggregateTile>
          }
          groupBy: {
            args: Prisma.TileGroupByArgs<ExtArgs>
            result: $Utils.Optional<TileGroupByOutputType>[]
          }
          count: {
            args: Prisma.TileCountArgs<ExtArgs>
            result: $Utils.Optional<TileCountAggregateOutputType> | number
          }
        }
      }
      PlayerBoard: {
        payload: Prisma.$PlayerBoardPayload<ExtArgs>
        fields: Prisma.PlayerBoardFieldRefs
        operations: {
          findUnique: {
            args: Prisma.PlayerBoardFindUniqueArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$PlayerBoardPayload> | null
          }
          findUniqueOrThrow: {
            args: Prisma.PlayerBoardFindUniqueOrThrowArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$PlayerBoardPayload>
          }
          findFirst: {
            args: Prisma.PlayerBoardFindFirstArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$PlayerBoardPayload> | null
          }
          findFirstOrThrow: {
            args: Prisma.PlayerBoardFindFirstOrThrowArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$PlayerBoardPayload>
          }
          findMany: {
            args: Prisma.PlayerBoardFindManyArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$PlayerBoardPayload>[]
          }
          create: {
            args: Prisma.PlayerBoardCreateArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$PlayerBoardPayload>
          }
          createMany: {
            args: Prisma.PlayerBoardCreateManyArgs<ExtArgs>
            result: BatchPayload
          }
          createManyAndReturn: {
            args: Prisma.PlayerBoardCreateManyAndReturnArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$PlayerBoardPayload>[]
          }
          delete: {
            args: Prisma.PlayerBoardDeleteArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$PlayerBoardPayload>
          }
          update: {
            args: Prisma.PlayerBoardUpdateArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$PlayerBoardPayload>
          }
          deleteMany: {
            args: Prisma.PlayerBoardDeleteManyArgs<ExtArgs>
            result: BatchPayload
          }
          updateMany: {
            args: Prisma.PlayerBoardUpdateManyArgs<ExtArgs>
            result: BatchPayload
          }
          updateManyAndReturn: {
            args: Prisma.PlayerBoardUpdateManyAndReturnArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$PlayerBoardPayload>[]
          }
          upsert: {
            args: Prisma.PlayerBoardUpsertArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$PlayerBoardPayload>
          }
          aggregate: {
            args: Prisma.PlayerBoardAggregateArgs<ExtArgs>
            result: $Utils.Optional<AggregatePlayerBoard>
          }
          groupBy: {
            args: Prisma.PlayerBoardGroupByArgs<ExtArgs>
            result: $Utils.Optional<PlayerBoardGroupByOutputType>[]
          }
          count: {
            args: Prisma.PlayerBoardCountArgs<ExtArgs>
            result: $Utils.Optional<PlayerBoardCountAggregateOutputType> | number
          }
        }
      }
      CompletedTile: {
        payload: Prisma.$CompletedTilePayload<ExtArgs>
        fields: Prisma.CompletedTileFieldRefs
        operations: {
          findUnique: {
            args: Prisma.CompletedTileFindUniqueArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$CompletedTilePayload> | null
          }
          findUniqueOrThrow: {
            args: Prisma.CompletedTileFindUniqueOrThrowArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$CompletedTilePayload>
          }
          findFirst: {
            args: Prisma.CompletedTileFindFirstArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$CompletedTilePayload> | null
          }
          findFirstOrThrow: {
            args: Prisma.CompletedTileFindFirstOrThrowArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$CompletedTilePayload>
          }
          findMany: {
            args: Prisma.CompletedTileFindManyArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$CompletedTilePayload>[]
          }
          create: {
            args: Prisma.CompletedTileCreateArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$CompletedTilePayload>
          }
          createMany: {
            args: Prisma.CompletedTileCreateManyArgs<ExtArgs>
            result: BatchPayload
          }
          createManyAndReturn: {
            args: Prisma.CompletedTileCreateManyAndReturnArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$CompletedTilePayload>[]
          }
          delete: {
            args: Prisma.CompletedTileDeleteArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$CompletedTilePayload>
          }
          update: {
            args: Prisma.CompletedTileUpdateArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$CompletedTilePayload>
          }
          deleteMany: {
            args: Prisma.CompletedTileDeleteManyArgs<ExtArgs>
            result: BatchPayload
          }
          updateMany: {
            args: Prisma.CompletedTileUpdateManyArgs<ExtArgs>
            result: BatchPayload
          }
          updateManyAndReturn: {
            args: Prisma.CompletedTileUpdateManyAndReturnArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$CompletedTilePayload>[]
          }
          upsert: {
            args: Prisma.CompletedTileUpsertArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$CompletedTilePayload>
          }
          aggregate: {
            args: Prisma.CompletedTileAggregateArgs<ExtArgs>
            result: $Utils.Optional<AggregateCompletedTile>
          }
          groupBy: {
            args: Prisma.CompletedTileGroupByArgs<ExtArgs>
            result: $Utils.Optional<CompletedTileGroupByOutputType>[]
          }
          count: {
            args: Prisma.CompletedTileCountArgs<ExtArgs>
            result: $Utils.Optional<CompletedTileCountAggregateOutputType> | number
          }
        }
      }
      Team: {
        payload: Prisma.$TeamPayload<ExtArgs>
        fields: Prisma.TeamFieldRefs
        operations: {
          findUnique: {
            args: Prisma.TeamFindUniqueArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$TeamPayload> | null
          }
          findUniqueOrThrow: {
            args: Prisma.TeamFindUniqueOrThrowArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$TeamPayload>
          }
          findFirst: {
            args: Prisma.TeamFindFirstArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$TeamPayload> | null
          }
          findFirstOrThrow: {
            args: Prisma.TeamFindFirstOrThrowArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$TeamPayload>
          }
          findMany: {
            args: Prisma.TeamFindManyArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$TeamPayload>[]
          }
          create: {
            args: Prisma.TeamCreateArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$TeamPayload>
          }
          createMany: {
            args: Prisma.TeamCreateManyArgs<ExtArgs>
            result: BatchPayload
          }
          createManyAndReturn: {
            args: Prisma.TeamCreateManyAndReturnArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$TeamPayload>[]
          }
          delete: {
            args: Prisma.TeamDeleteArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$TeamPayload>
          }
          update: {
            args: Prisma.TeamUpdateArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$TeamPayload>
          }
          deleteMany: {
            args: Prisma.TeamDeleteManyArgs<ExtArgs>
            result: BatchPayload
          }
          updateMany: {
            args: Prisma.TeamUpdateManyArgs<ExtArgs>
            result: BatchPayload
          }
          updateManyAndReturn: {
            args: Prisma.TeamUpdateManyAndReturnArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$TeamPayload>[]
          }
          upsert: {
            args: Prisma.TeamUpsertArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$TeamPayload>
          }
          aggregate: {
            args: Prisma.TeamAggregateArgs<ExtArgs>
            result: $Utils.Optional<AggregateTeam>
          }
          groupBy: {
            args: Prisma.TeamGroupByArgs<ExtArgs>
            result: $Utils.Optional<TeamGroupByOutputType>[]
          }
          count: {
            args: Prisma.TeamCountArgs<ExtArgs>
            result: $Utils.Optional<TeamCountAggregateOutputType> | number
          }
        }
      }
      TeamMember: {
        payload: Prisma.$TeamMemberPayload<ExtArgs>
        fields: Prisma.TeamMemberFieldRefs
        operations: {
          findUnique: {
            args: Prisma.TeamMemberFindUniqueArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$TeamMemberPayload> | null
          }
          findUniqueOrThrow: {
            args: Prisma.TeamMemberFindUniqueOrThrowArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$TeamMemberPayload>
          }
          findFirst: {
            args: Prisma.TeamMemberFindFirstArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$TeamMemberPayload> | null
          }
          findFirstOrThrow: {
            args: Prisma.TeamMemberFindFirstOrThrowArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$TeamMemberPayload>
          }
          findMany: {
            args: Prisma.TeamMemberFindManyArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$TeamMemberPayload>[]
          }
          create: {
            args: Prisma.TeamMemberCreateArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$TeamMemberPayload>
          }
          createMany: {
            args: Prisma.TeamMemberCreateManyArgs<ExtArgs>
            result: BatchPayload
          }
          createManyAndReturn: {
            args: Prisma.TeamMemberCreateManyAndReturnArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$TeamMemberPayload>[]
          }
          delete: {
            args: Prisma.TeamMemberDeleteArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$TeamMemberPayload>
          }
          update: {
            args: Prisma.TeamMemberUpdateArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$TeamMemberPayload>
          }
          deleteMany: {
            args: Prisma.TeamMemberDeleteManyArgs<ExtArgs>
            result: BatchPayload
          }
          updateMany: {
            args: Prisma.TeamMemberUpdateManyArgs<ExtArgs>
            result: BatchPayload
          }
          updateManyAndReturn: {
            args: Prisma.TeamMemberUpdateManyAndReturnArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$TeamMemberPayload>[]
          }
          upsert: {
            args: Prisma.TeamMemberUpsertArgs<ExtArgs>
            result: $Utils.PayloadToResult<Prisma.$TeamMemberPayload>
          }
          aggregate: {
            args: Prisma.TeamMemberAggregateArgs<ExtArgs>
            result: $Utils.Optional<AggregateTeamMember>
          }
          groupBy: {
            args: Prisma.TeamMemberGroupByArgs<ExtArgs>
            result: $Utils.Optional<TeamMemberGroupByOutputType>[]
          }
          count: {
            args: Prisma.TeamMemberCountArgs<ExtArgs>
            result: $Utils.Optional<TeamMemberCountAggregateOutputType> | number
          }
        }
      }
    }
  } & {
    other: {
      payload: any
      operations: {
        $executeRaw: {
          args: [query: TemplateStringsArray | Prisma.Sql, ...values: any[]],
          result: any
        }
        $executeRawUnsafe: {
          args: [query: string, ...values: any[]],
          result: any
        }
        $queryRaw: {
          args: [query: TemplateStringsArray | Prisma.Sql, ...values: any[]],
          result: any
        }
        $queryRawUnsafe: {
          args: [query: string, ...values: any[]],
          result: any
        }
      }
    }
  }
  export const defineExtension: $Extensions.ExtendsHook<"define", Prisma.TypeMapCb, $Extensions.DefaultArgs>
  export type DefaultPrismaClient = PrismaClient
  export type ErrorFormat = 'pretty' | 'colorless' | 'minimal'
  export interface PrismaClientOptions {
    /**
     * @default "colorless"
     */
    errorFormat?: ErrorFormat
    /**
     * @example
     * ```
     * // Shorthand for `emit: 'stdout'`
     * log: ['query', 'info', 'warn', 'error']
     * 
     * // Emit as events only
     * log: [
     *   { emit: 'event', level: 'query' },
     *   { emit: 'event', level: 'info' },
     *   { emit: 'event', level: 'warn' }
     *   { emit: 'event', level: 'error' }
     * ]
     * 
     * / Emit as events and log to stdout
     * og: [
     *  { emit: 'stdout', level: 'query' },
     *  { emit: 'stdout', level: 'info' },
     *  { emit: 'stdout', level: 'warn' }
     *  { emit: 'stdout', level: 'error' }
     * 
     * ```
     * Read more in our [docs](https://pris.ly/d/logging).
     */
    log?: (LogLevel | LogDefinition)[]
    /**
     * The default values for transactionOptions
     * maxWait ?= 2000
     * timeout ?= 5000
     */
    transactionOptions?: {
      maxWait?: number
      timeout?: number
      isolationLevel?: Prisma.TransactionIsolationLevel
    }
    /**
     * Instance of a Driver Adapter, e.g., like one provided by `@prisma/adapter-planetscale`
     */
    adapter?: runtime.SqlDriverAdapterFactory
    /**
     * Prisma Accelerate URL allowing the client to connect through Accelerate instead of a direct database.
     */
    accelerateUrl?: string
    /**
     * Global configuration for omitting model fields by default.
     * 
     * @example
     * ```
     * const prisma = new PrismaClient({
     *   omit: {
     *     user: {
     *       password: true
     *     }
     *   }
     * })
     * ```
     */
    omit?: Prisma.GlobalOmitConfig
    /**
     * SQL commenter plugins that add metadata to SQL queries as comments.
     * Comments follow the sqlcommenter format: https://google.github.io/sqlcommenter/
     * 
     * @example
     * ```
     * const prisma = new PrismaClient({
     *   adapter,
     *   comments: [
     *     traceContext(),
     *     queryInsights(),
     *   ],
     * })
     * ```
     */
    comments?: runtime.SqlCommenterPlugin[]
  }
  export type GlobalOmitConfig = {
    role?: RoleOmit
    user?: UserOmit
    userPermission?: UserPermissionOmit
    userRole?: UserRoleOmit
    task?: TaskOmit
    board?: BoardOmit
    boardTeam?: BoardTeamOmit
    boardAuthor?: BoardAuthorOmit
    tile?: TileOmit
    playerBoard?: PlayerBoardOmit
    completedTile?: CompletedTileOmit
    team?: TeamOmit
    teamMember?: TeamMemberOmit
  }

  /* Types for Logging */
  export type LogLevel = 'info' | 'query' | 'warn' | 'error'
  export type LogDefinition = {
    level: LogLevel
    emit: 'stdout' | 'event'
  }

  export type CheckIsLogLevel<T> = T extends LogLevel ? T : never;

  export type GetLogType<T> = CheckIsLogLevel<
    T extends LogDefinition ? T['level'] : T
  >;

  export type GetEvents<T extends any[]> = T extends Array<LogLevel | LogDefinition>
    ? GetLogType<T[number]>
    : never;

  export type QueryEvent = {
    timestamp: Date
    query: string
    params: string
    duration: number
    target: string
  }

  export type LogEvent = {
    timestamp: Date
    message: string
    target: string
  }
  /* End Types for Logging */


  export type PrismaAction =
    | 'findUnique'
    | 'findUniqueOrThrow'
    | 'findMany'
    | 'findFirst'
    | 'findFirstOrThrow'
    | 'create'
    | 'createMany'
    | 'createManyAndReturn'
    | 'update'
    | 'updateMany'
    | 'updateManyAndReturn'
    | 'upsert'
    | 'delete'
    | 'deleteMany'
    | 'executeRaw'
    | 'queryRaw'
    | 'aggregate'
    | 'count'
    | 'runCommandRaw'
    | 'findRaw'
    | 'groupBy'

  // tested in getLogLevel.test.ts
  export function getLogLevel(log: Array<LogLevel | LogDefinition>): LogLevel | undefined;

  /**
   * `PrismaClient` proxy available in interactive transactions.
   */
  export type TransactionClient = Omit<Prisma.DefaultPrismaClient, runtime.ITXClientDenyList>

  export type Datasource = {
    url?: string
  }

  /**
   * Count Types
   */


  /**
   * Count Type RoleCountOutputType
   */

  export type RoleCountOutputType = {
    userRoles: number
  }

  export type RoleCountOutputTypeSelect<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    userRoles?: boolean | RoleCountOutputTypeCountUserRolesArgs
  }

  // Custom InputTypes
  /**
   * RoleCountOutputType without action
   */
  export type RoleCountOutputTypeDefaultArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the RoleCountOutputType
     */
    select?: RoleCountOutputTypeSelect<ExtArgs> | null
  }

  /**
   * RoleCountOutputType without action
   */
  export type RoleCountOutputTypeCountUserRolesArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    where?: UserRoleWhereInput
  }


  /**
   * Count Type UserCountOutputType
   */

  export type UserCountOutputType = {
    userRoles: number
    userPermissions: number
    boardAuthors: number
    playerBoards: number
    teamMembers: number
  }

  export type UserCountOutputTypeSelect<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    userRoles?: boolean | UserCountOutputTypeCountUserRolesArgs
    userPermissions?: boolean | UserCountOutputTypeCountUserPermissionsArgs
    boardAuthors?: boolean | UserCountOutputTypeCountBoardAuthorsArgs
    playerBoards?: boolean | UserCountOutputTypeCountPlayerBoardsArgs
    teamMembers?: boolean | UserCountOutputTypeCountTeamMembersArgs
  }

  // Custom InputTypes
  /**
   * UserCountOutputType without action
   */
  export type UserCountOutputTypeDefaultArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the UserCountOutputType
     */
    select?: UserCountOutputTypeSelect<ExtArgs> | null
  }

  /**
   * UserCountOutputType without action
   */
  export type UserCountOutputTypeCountUserRolesArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    where?: UserRoleWhereInput
  }

  /**
   * UserCountOutputType without action
   */
  export type UserCountOutputTypeCountUserPermissionsArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    where?: UserPermissionWhereInput
  }

  /**
   * UserCountOutputType without action
   */
  export type UserCountOutputTypeCountBoardAuthorsArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    where?: BoardAuthorWhereInput
  }

  /**
   * UserCountOutputType without action
   */
  export type UserCountOutputTypeCountPlayerBoardsArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    where?: PlayerBoardWhereInput
  }

  /**
   * UserCountOutputType without action
   */
  export type UserCountOutputTypeCountTeamMembersArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    where?: TeamMemberWhereInput
  }


  /**
   * Count Type TaskCountOutputType
   */

  export type TaskCountOutputType = {
    tiles: number
  }

  export type TaskCountOutputTypeSelect<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    tiles?: boolean | TaskCountOutputTypeCountTilesArgs
  }

  // Custom InputTypes
  /**
   * TaskCountOutputType without action
   */
  export type TaskCountOutputTypeDefaultArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the TaskCountOutputType
     */
    select?: TaskCountOutputTypeSelect<ExtArgs> | null
  }

  /**
   * TaskCountOutputType without action
   */
  export type TaskCountOutputTypeCountTilesArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    where?: TileWhereInput
  }


  /**
   * Count Type BoardCountOutputType
   */

  export type BoardCountOutputType = {
    authors: number
    tiles: number
    playerBoards: number
    boardTeams: number
  }

  export type BoardCountOutputTypeSelect<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    authors?: boolean | BoardCountOutputTypeCountAuthorsArgs
    tiles?: boolean | BoardCountOutputTypeCountTilesArgs
    playerBoards?: boolean | BoardCountOutputTypeCountPlayerBoardsArgs
    boardTeams?: boolean | BoardCountOutputTypeCountBoardTeamsArgs
  }

  // Custom InputTypes
  /**
   * BoardCountOutputType without action
   */
  export type BoardCountOutputTypeDefaultArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the BoardCountOutputType
     */
    select?: BoardCountOutputTypeSelect<ExtArgs> | null
  }

  /**
   * BoardCountOutputType without action
   */
  export type BoardCountOutputTypeCountAuthorsArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    where?: BoardAuthorWhereInput
  }

  /**
   * BoardCountOutputType without action
   */
  export type BoardCountOutputTypeCountTilesArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    where?: TileWhereInput
  }

  /**
   * BoardCountOutputType without action
   */
  export type BoardCountOutputTypeCountPlayerBoardsArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    where?: PlayerBoardWhereInput
  }

  /**
   * BoardCountOutputType without action
   */
  export type BoardCountOutputTypeCountBoardTeamsArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    where?: BoardTeamWhereInput
  }


  /**
   * Count Type TileCountOutputType
   */

  export type TileCountOutputType = {
    completedTiles: number
  }

  export type TileCountOutputTypeSelect<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    completedTiles?: boolean | TileCountOutputTypeCountCompletedTilesArgs
  }

  // Custom InputTypes
  /**
   * TileCountOutputType without action
   */
  export type TileCountOutputTypeDefaultArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the TileCountOutputType
     */
    select?: TileCountOutputTypeSelect<ExtArgs> | null
  }

  /**
   * TileCountOutputType without action
   */
  export type TileCountOutputTypeCountCompletedTilesArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    where?: CompletedTileWhereInput
  }


  /**
   * Count Type PlayerBoardCountOutputType
   */

  export type PlayerBoardCountOutputType = {
    completedTiles: number
  }

  export type PlayerBoardCountOutputTypeSelect<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    completedTiles?: boolean | PlayerBoardCountOutputTypeCountCompletedTilesArgs
  }

  // Custom InputTypes
  /**
   * PlayerBoardCountOutputType without action
   */
  export type PlayerBoardCountOutputTypeDefaultArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the PlayerBoardCountOutputType
     */
    select?: PlayerBoardCountOutputTypeSelect<ExtArgs> | null
  }

  /**
   * PlayerBoardCountOutputType without action
   */
  export type PlayerBoardCountOutputTypeCountCompletedTilesArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    where?: CompletedTileWhereInput
  }


  /**
   * Count Type TeamCountOutputType
   */

  export type TeamCountOutputType = {
    members: number
    boardTeams: number
    playerBoards: number
  }

  export type TeamCountOutputTypeSelect<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    members?: boolean | TeamCountOutputTypeCountMembersArgs
    boardTeams?: boolean | TeamCountOutputTypeCountBoardTeamsArgs
    playerBoards?: boolean | TeamCountOutputTypeCountPlayerBoardsArgs
  }

  // Custom InputTypes
  /**
   * TeamCountOutputType without action
   */
  export type TeamCountOutputTypeDefaultArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the TeamCountOutputType
     */
    select?: TeamCountOutputTypeSelect<ExtArgs> | null
  }

  /**
   * TeamCountOutputType without action
   */
  export type TeamCountOutputTypeCountMembersArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    where?: TeamMemberWhereInput
  }

  /**
   * TeamCountOutputType without action
   */
  export type TeamCountOutputTypeCountBoardTeamsArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    where?: BoardTeamWhereInput
  }

  /**
   * TeamCountOutputType without action
   */
  export type TeamCountOutputTypeCountPlayerBoardsArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    where?: PlayerBoardWhereInput
  }


  /**
   * Models
   */

  /**
   * Model Role
   */

  export type AggregateRole = {
    _count: RoleCountAggregateOutputType | null
    _min: RoleMinAggregateOutputType | null
    _max: RoleMaxAggregateOutputType | null
  }

  export type RoleMinAggregateOutputType = {
    id: string | null
    name: string | null
    description: string | null
    createdAt: Date | null
    updatedAt: Date | null
  }

  export type RoleMaxAggregateOutputType = {
    id: string | null
    name: string | null
    description: string | null
    createdAt: Date | null
    updatedAt: Date | null
  }

  export type RoleCountAggregateOutputType = {
    id: number
    name: number
    description: number
    createdAt: number
    updatedAt: number
    _all: number
  }


  export type RoleMinAggregateInputType = {
    id?: true
    name?: true
    description?: true
    createdAt?: true
    updatedAt?: true
  }

  export type RoleMaxAggregateInputType = {
    id?: true
    name?: true
    description?: true
    createdAt?: true
    updatedAt?: true
  }

  export type RoleCountAggregateInputType = {
    id?: true
    name?: true
    description?: true
    createdAt?: true
    updatedAt?: true
    _all?: true
  }

  export type RoleAggregateArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Filter which Role to aggregate.
     */
    where?: RoleWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of Roles to fetch.
     */
    orderBy?: RoleOrderByWithRelationInput | RoleOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the start position
     */
    cursor?: RoleWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` Roles from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` Roles.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Count returned Roles
    **/
    _count?: true | RoleCountAggregateInputType
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Select which fields to find the minimum value
    **/
    _min?: RoleMinAggregateInputType
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Select which fields to find the maximum value
    **/
    _max?: RoleMaxAggregateInputType
  }

  export type GetRoleAggregateType<T extends RoleAggregateArgs> = {
        [P in keyof T & keyof AggregateRole]: P extends '_count' | 'count'
      ? T[P] extends true
        ? number
        : GetScalarType<T[P], AggregateRole[P]>
      : GetScalarType<T[P], AggregateRole[P]>
  }




  export type RoleGroupByArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    where?: RoleWhereInput
    orderBy?: RoleOrderByWithAggregationInput | RoleOrderByWithAggregationInput[]
    by: RoleScalarFieldEnum[] | RoleScalarFieldEnum
    having?: RoleScalarWhereWithAggregatesInput
    take?: number
    skip?: number
    _count?: RoleCountAggregateInputType | true
    _min?: RoleMinAggregateInputType
    _max?: RoleMaxAggregateInputType
  }

  export type RoleGroupByOutputType = {
    id: string
    name: string
    description: string | null
    createdAt: Date
    updatedAt: Date
    _count: RoleCountAggregateOutputType | null
    _min: RoleMinAggregateOutputType | null
    _max: RoleMaxAggregateOutputType | null
  }

  type GetRoleGroupByPayload<T extends RoleGroupByArgs> = Prisma.PrismaPromise<
    Array<
      PickEnumerable<RoleGroupByOutputType, T['by']> &
        {
          [P in ((keyof T) & (keyof RoleGroupByOutputType))]: P extends '_count'
            ? T[P] extends boolean
              ? number
              : GetScalarType<T[P], RoleGroupByOutputType[P]>
            : GetScalarType<T[P], RoleGroupByOutputType[P]>
        }
      >
    >


  export type RoleSelect<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetSelect<{
    id?: boolean
    name?: boolean
    description?: boolean
    createdAt?: boolean
    updatedAt?: boolean
    userRoles?: boolean | Role$userRolesArgs<ExtArgs>
    _count?: boolean | RoleCountOutputTypeDefaultArgs<ExtArgs>
  }, ExtArgs["result"]["role"]>

  export type RoleSelectCreateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetSelect<{
    id?: boolean
    name?: boolean
    description?: boolean
    createdAt?: boolean
    updatedAt?: boolean
  }, ExtArgs["result"]["role"]>

  export type RoleSelectUpdateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetSelect<{
    id?: boolean
    name?: boolean
    description?: boolean
    createdAt?: boolean
    updatedAt?: boolean
  }, ExtArgs["result"]["role"]>

  export type RoleSelectScalar = {
    id?: boolean
    name?: boolean
    description?: boolean
    createdAt?: boolean
    updatedAt?: boolean
  }

  export type RoleOmit<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetOmit<"id" | "name" | "description" | "createdAt" | "updatedAt", ExtArgs["result"]["role"]>
  export type RoleInclude<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    userRoles?: boolean | Role$userRolesArgs<ExtArgs>
    _count?: boolean | RoleCountOutputTypeDefaultArgs<ExtArgs>
  }
  export type RoleIncludeCreateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {}
  export type RoleIncludeUpdateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {}

  export type $RolePayload<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    name: "Role"
    objects: {
      userRoles: Prisma.$UserRolePayload<ExtArgs>[]
    }
    scalars: $Extensions.GetPayloadResult<{
      id: string
      name: string
      description: string | null
      createdAt: Date
      updatedAt: Date
    }, ExtArgs["result"]["role"]>
    composites: {}
  }

  type RoleGetPayload<S extends boolean | null | undefined | RoleDefaultArgs> = $Result.GetResult<Prisma.$RolePayload, S>

  type RoleCountArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> =
    Omit<RoleFindManyArgs, 'select' | 'include' | 'distinct' | 'omit'> & {
      select?: RoleCountAggregateInputType | true
    }

  export interface RoleDelegate<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs, GlobalOmitOptions = {}> {
    [K: symbol]: { types: Prisma.TypeMap<ExtArgs>['model']['Role'], meta: { name: 'Role' } }
    /**
     * Find zero or one Role that matches the filter.
     * @param {RoleFindUniqueArgs} args - Arguments to find a Role
     * @example
     * // Get one Role
     * const role = await prisma.role.findUnique({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findUnique<T extends RoleFindUniqueArgs>(args: SelectSubset<T, RoleFindUniqueArgs<ExtArgs>>): Prisma__RoleClient<$Result.GetResult<Prisma.$RolePayload<ExtArgs>, T, "findUnique", GlobalOmitOptions> | null, null, ExtArgs, GlobalOmitOptions>

    /**
     * Find one Role that matches the filter or throw an error with `error.code='P2025'`
     * if no matches were found.
     * @param {RoleFindUniqueOrThrowArgs} args - Arguments to find a Role
     * @example
     * // Get one Role
     * const role = await prisma.role.findUniqueOrThrow({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findUniqueOrThrow<T extends RoleFindUniqueOrThrowArgs>(args: SelectSubset<T, RoleFindUniqueOrThrowArgs<ExtArgs>>): Prisma__RoleClient<$Result.GetResult<Prisma.$RolePayload<ExtArgs>, T, "findUniqueOrThrow", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Find the first Role that matches the filter.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {RoleFindFirstArgs} args - Arguments to find a Role
     * @example
     * // Get one Role
     * const role = await prisma.role.findFirst({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findFirst<T extends RoleFindFirstArgs>(args?: SelectSubset<T, RoleFindFirstArgs<ExtArgs>>): Prisma__RoleClient<$Result.GetResult<Prisma.$RolePayload<ExtArgs>, T, "findFirst", GlobalOmitOptions> | null, null, ExtArgs, GlobalOmitOptions>

    /**
     * Find the first Role that matches the filter or
     * throw `PrismaKnownClientError` with `P2025` code if no matches were found.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {RoleFindFirstOrThrowArgs} args - Arguments to find a Role
     * @example
     * // Get one Role
     * const role = await prisma.role.findFirstOrThrow({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findFirstOrThrow<T extends RoleFindFirstOrThrowArgs>(args?: SelectSubset<T, RoleFindFirstOrThrowArgs<ExtArgs>>): Prisma__RoleClient<$Result.GetResult<Prisma.$RolePayload<ExtArgs>, T, "findFirstOrThrow", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Find zero or more Roles that matches the filter.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {RoleFindManyArgs} args - Arguments to filter and select certain fields only.
     * @example
     * // Get all Roles
     * const roles = await prisma.role.findMany()
     * 
     * // Get first 10 Roles
     * const roles = await prisma.role.findMany({ take: 10 })
     * 
     * // Only select the `id`
     * const roleWithIdOnly = await prisma.role.findMany({ select: { id: true } })
     * 
     */
    findMany<T extends RoleFindManyArgs>(args?: SelectSubset<T, RoleFindManyArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$RolePayload<ExtArgs>, T, "findMany", GlobalOmitOptions>>

    /**
     * Create a Role.
     * @param {RoleCreateArgs} args - Arguments to create a Role.
     * @example
     * // Create one Role
     * const Role = await prisma.role.create({
     *   data: {
     *     // ... data to create a Role
     *   }
     * })
     * 
     */
    create<T extends RoleCreateArgs>(args: SelectSubset<T, RoleCreateArgs<ExtArgs>>): Prisma__RoleClient<$Result.GetResult<Prisma.$RolePayload<ExtArgs>, T, "create", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Create many Roles.
     * @param {RoleCreateManyArgs} args - Arguments to create many Roles.
     * @example
     * // Create many Roles
     * const role = await prisma.role.createMany({
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     *     
     */
    createMany<T extends RoleCreateManyArgs>(args?: SelectSubset<T, RoleCreateManyArgs<ExtArgs>>): Prisma.PrismaPromise<BatchPayload>

    /**
     * Create many Roles and returns the data saved in the database.
     * @param {RoleCreateManyAndReturnArgs} args - Arguments to create many Roles.
     * @example
     * // Create many Roles
     * const role = await prisma.role.createManyAndReturn({
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * 
     * // Create many Roles and only return the `id`
     * const roleWithIdOnly = await prisma.role.createManyAndReturn({
     *   select: { id: true },
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * 
     */
    createManyAndReturn<T extends RoleCreateManyAndReturnArgs>(args?: SelectSubset<T, RoleCreateManyAndReturnArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$RolePayload<ExtArgs>, T, "createManyAndReturn", GlobalOmitOptions>>

    /**
     * Delete a Role.
     * @param {RoleDeleteArgs} args - Arguments to delete one Role.
     * @example
     * // Delete one Role
     * const Role = await prisma.role.delete({
     *   where: {
     *     // ... filter to delete one Role
     *   }
     * })
     * 
     */
    delete<T extends RoleDeleteArgs>(args: SelectSubset<T, RoleDeleteArgs<ExtArgs>>): Prisma__RoleClient<$Result.GetResult<Prisma.$RolePayload<ExtArgs>, T, "delete", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Update one Role.
     * @param {RoleUpdateArgs} args - Arguments to update one Role.
     * @example
     * // Update one Role
     * const role = await prisma.role.update({
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: {
     *     // ... provide data here
     *   }
     * })
     * 
     */
    update<T extends RoleUpdateArgs>(args: SelectSubset<T, RoleUpdateArgs<ExtArgs>>): Prisma__RoleClient<$Result.GetResult<Prisma.$RolePayload<ExtArgs>, T, "update", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Delete zero or more Roles.
     * @param {RoleDeleteManyArgs} args - Arguments to filter Roles to delete.
     * @example
     * // Delete a few Roles
     * const { count } = await prisma.role.deleteMany({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     * 
     */
    deleteMany<T extends RoleDeleteManyArgs>(args?: SelectSubset<T, RoleDeleteManyArgs<ExtArgs>>): Prisma.PrismaPromise<BatchPayload>

    /**
     * Update zero or more Roles.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {RoleUpdateManyArgs} args - Arguments to update one or more rows.
     * @example
     * // Update many Roles
     * const role = await prisma.role.updateMany({
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: {
     *     // ... provide data here
     *   }
     * })
     * 
     */
    updateMany<T extends RoleUpdateManyArgs>(args: SelectSubset<T, RoleUpdateManyArgs<ExtArgs>>): Prisma.PrismaPromise<BatchPayload>

    /**
     * Update zero or more Roles and returns the data updated in the database.
     * @param {RoleUpdateManyAndReturnArgs} args - Arguments to update many Roles.
     * @example
     * // Update many Roles
     * const role = await prisma.role.updateManyAndReturn({
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * 
     * // Update zero or more Roles and only return the `id`
     * const roleWithIdOnly = await prisma.role.updateManyAndReturn({
     *   select: { id: true },
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * 
     */
    updateManyAndReturn<T extends RoleUpdateManyAndReturnArgs>(args: SelectSubset<T, RoleUpdateManyAndReturnArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$RolePayload<ExtArgs>, T, "updateManyAndReturn", GlobalOmitOptions>>

    /**
     * Create or update one Role.
     * @param {RoleUpsertArgs} args - Arguments to update or create a Role.
     * @example
     * // Update or create a Role
     * const role = await prisma.role.upsert({
     *   create: {
     *     // ... data to create a Role
     *   },
     *   update: {
     *     // ... in case it already exists, update
     *   },
     *   where: {
     *     // ... the filter for the Role we want to update
     *   }
     * })
     */
    upsert<T extends RoleUpsertArgs>(args: SelectSubset<T, RoleUpsertArgs<ExtArgs>>): Prisma__RoleClient<$Result.GetResult<Prisma.$RolePayload<ExtArgs>, T, "upsert", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>


    /**
     * Count the number of Roles.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {RoleCountArgs} args - Arguments to filter Roles to count.
     * @example
     * // Count the number of Roles
     * const count = await prisma.role.count({
     *   where: {
     *     // ... the filter for the Roles we want to count
     *   }
     * })
    **/
    count<T extends RoleCountArgs>(
      args?: Subset<T, RoleCountArgs>,
    ): Prisma.PrismaPromise<
      T extends $Utils.Record<'select', any>
        ? T['select'] extends true
          ? number
          : GetScalarType<T['select'], RoleCountAggregateOutputType>
        : number
    >

    /**
     * Allows you to perform aggregations operations on a Role.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {RoleAggregateArgs} args - Select which aggregations you would like to apply and on what fields.
     * @example
     * // Ordered by age ascending
     * // Where email contains prisma.io
     * // Limited to the 10 users
     * const aggregations = await prisma.user.aggregate({
     *   _avg: {
     *     age: true,
     *   },
     *   where: {
     *     email: {
     *       contains: "prisma.io",
     *     },
     *   },
     *   orderBy: {
     *     age: "asc",
     *   },
     *   take: 10,
     * })
    **/
    aggregate<T extends RoleAggregateArgs>(args: Subset<T, RoleAggregateArgs>): Prisma.PrismaPromise<GetRoleAggregateType<T>>

    /**
     * Group by Role.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {RoleGroupByArgs} args - Group by arguments.
     * @example
     * // Group by city, order by createdAt, get count
     * const result = await prisma.user.groupBy({
     *   by: ['city', 'createdAt'],
     *   orderBy: {
     *     createdAt: true
     *   },
     *   _count: {
     *     _all: true
     *   },
     * })
     * 
    **/
    groupBy<
      T extends RoleGroupByArgs,
      HasSelectOrTake extends Or<
        Extends<'skip', Keys<T>>,
        Extends<'take', Keys<T>>
      >,
      OrderByArg extends True extends HasSelectOrTake
        ? { orderBy: RoleGroupByArgs['orderBy'] }
        : { orderBy?: RoleGroupByArgs['orderBy'] },
      OrderFields extends ExcludeUnderscoreKeys<Keys<MaybeTupleToUnion<T['orderBy']>>>,
      ByFields extends MaybeTupleToUnion<T['by']>,
      ByValid extends Has<ByFields, OrderFields>,
      HavingFields extends GetHavingFields<T['having']>,
      HavingValid extends Has<ByFields, HavingFields>,
      ByEmpty extends T['by'] extends never[] ? True : False,
      InputErrors extends ByEmpty extends True
      ? `Error: "by" must not be empty.`
      : HavingValid extends False
      ? {
          [P in HavingFields]: P extends ByFields
            ? never
            : P extends string
            ? `Error: Field "${P}" used in "having" needs to be provided in "by".`
            : [
                Error,
                'Field ',
                P,
                ` in "having" needs to be provided in "by"`,
              ]
        }[HavingFields]
      : 'take' extends Keys<T>
      ? 'orderBy' extends Keys<T>
        ? ByValid extends True
          ? {}
          : {
              [P in OrderFields]: P extends ByFields
                ? never
                : `Error: Field "${P}" in "orderBy" needs to be provided in "by"`
            }[OrderFields]
        : 'Error: If you provide "take", you also need to provide "orderBy"'
      : 'skip' extends Keys<T>
      ? 'orderBy' extends Keys<T>
        ? ByValid extends True
          ? {}
          : {
              [P in OrderFields]: P extends ByFields
                ? never
                : `Error: Field "${P}" in "orderBy" needs to be provided in "by"`
            }[OrderFields]
        : 'Error: If you provide "skip", you also need to provide "orderBy"'
      : ByValid extends True
      ? {}
      : {
          [P in OrderFields]: P extends ByFields
            ? never
            : `Error: Field "${P}" in "orderBy" needs to be provided in "by"`
        }[OrderFields]
    >(args: SubsetIntersection<T, RoleGroupByArgs, OrderByArg> & InputErrors): {} extends InputErrors ? GetRoleGroupByPayload<T> : Prisma.PrismaPromise<InputErrors>
  /**
   * Fields of the Role model
   */
  readonly fields: RoleFieldRefs;
  }

  /**
   * The delegate class that acts as a "Promise-like" for Role.
   * Why is this prefixed with `Prisma__`?
   * Because we want to prevent naming conflicts as mentioned in
   * https://github.com/prisma/prisma-client-js/issues/707
   */
  export interface Prisma__RoleClient<T, Null = never, ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs, GlobalOmitOptions = {}> extends Prisma.PrismaPromise<T> {
    readonly [Symbol.toStringTag]: "PrismaPromise"
    userRoles<T extends Role$userRolesArgs<ExtArgs> = {}>(args?: Subset<T, Role$userRolesArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$UserRolePayload<ExtArgs>, T, "findMany", GlobalOmitOptions> | Null>
    /**
     * Attaches callbacks for the resolution and/or rejection of the Promise.
     * @param onfulfilled The callback to execute when the Promise is resolved.
     * @param onrejected The callback to execute when the Promise is rejected.
     * @returns A Promise for the completion of which ever callback is executed.
     */
    then<TResult1 = T, TResult2 = never>(onfulfilled?: ((value: T) => TResult1 | PromiseLike<TResult1>) | undefined | null, onrejected?: ((reason: any) => TResult2 | PromiseLike<TResult2>) | undefined | null): $Utils.JsPromise<TResult1 | TResult2>
    /**
     * Attaches a callback for only the rejection of the Promise.
     * @param onrejected The callback to execute when the Promise is rejected.
     * @returns A Promise for the completion of the callback.
     */
    catch<TResult = never>(onrejected?: ((reason: any) => TResult | PromiseLike<TResult>) | undefined | null): $Utils.JsPromise<T | TResult>
    /**
     * Attaches a callback that is invoked when the Promise is settled (fulfilled or rejected). The
     * resolved value cannot be modified from the callback.
     * @param onfinally The callback to execute when the Promise is settled (fulfilled or rejected).
     * @returns A Promise for the completion of the callback.
     */
    finally(onfinally?: (() => void) | undefined | null): $Utils.JsPromise<T>
  }




  /**
   * Fields of the Role model
   */
  interface RoleFieldRefs {
    readonly id: FieldRef<"Role", 'String'>
    readonly name: FieldRef<"Role", 'String'>
    readonly description: FieldRef<"Role", 'String'>
    readonly createdAt: FieldRef<"Role", 'DateTime'>
    readonly updatedAt: FieldRef<"Role", 'DateTime'>
  }
    

  // Custom InputTypes
  /**
   * Role findUnique
   */
  export type RoleFindUniqueArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Role
     */
    select?: RoleSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Role
     */
    omit?: RoleOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: RoleInclude<ExtArgs> | null
    /**
     * Filter, which Role to fetch.
     */
    where: RoleWhereUniqueInput
  }

  /**
   * Role findUniqueOrThrow
   */
  export type RoleFindUniqueOrThrowArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Role
     */
    select?: RoleSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Role
     */
    omit?: RoleOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: RoleInclude<ExtArgs> | null
    /**
     * Filter, which Role to fetch.
     */
    where: RoleWhereUniqueInput
  }

  /**
   * Role findFirst
   */
  export type RoleFindFirstArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Role
     */
    select?: RoleSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Role
     */
    omit?: RoleOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: RoleInclude<ExtArgs> | null
    /**
     * Filter, which Role to fetch.
     */
    where?: RoleWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of Roles to fetch.
     */
    orderBy?: RoleOrderByWithRelationInput | RoleOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the position for searching for Roles.
     */
    cursor?: RoleWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` Roles from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` Roles.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/distinct Distinct Docs}
     * 
     * Filter by unique combinations of Roles.
     */
    distinct?: RoleScalarFieldEnum | RoleScalarFieldEnum[]
  }

  /**
   * Role findFirstOrThrow
   */
  export type RoleFindFirstOrThrowArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Role
     */
    select?: RoleSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Role
     */
    omit?: RoleOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: RoleInclude<ExtArgs> | null
    /**
     * Filter, which Role to fetch.
     */
    where?: RoleWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of Roles to fetch.
     */
    orderBy?: RoleOrderByWithRelationInput | RoleOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the position for searching for Roles.
     */
    cursor?: RoleWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` Roles from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` Roles.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/distinct Distinct Docs}
     * 
     * Filter by unique combinations of Roles.
     */
    distinct?: RoleScalarFieldEnum | RoleScalarFieldEnum[]
  }

  /**
   * Role findMany
   */
  export type RoleFindManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Role
     */
    select?: RoleSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Role
     */
    omit?: RoleOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: RoleInclude<ExtArgs> | null
    /**
     * Filter, which Roles to fetch.
     */
    where?: RoleWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of Roles to fetch.
     */
    orderBy?: RoleOrderByWithRelationInput | RoleOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the position for listing Roles.
     */
    cursor?: RoleWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` Roles from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` Roles.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/distinct Distinct Docs}
     * 
     * Filter by unique combinations of Roles.
     */
    distinct?: RoleScalarFieldEnum | RoleScalarFieldEnum[]
  }

  /**
   * Role create
   */
  export type RoleCreateArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Role
     */
    select?: RoleSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Role
     */
    omit?: RoleOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: RoleInclude<ExtArgs> | null
    /**
     * The data needed to create a Role.
     */
    data: XOR<RoleCreateInput, RoleUncheckedCreateInput>
  }

  /**
   * Role createMany
   */
  export type RoleCreateManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * The data used to create many Roles.
     */
    data: RoleCreateManyInput | RoleCreateManyInput[]
    skipDuplicates?: boolean
  }

  /**
   * Role createManyAndReturn
   */
  export type RoleCreateManyAndReturnArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Role
     */
    select?: RoleSelectCreateManyAndReturn<ExtArgs> | null
    /**
     * Omit specific fields from the Role
     */
    omit?: RoleOmit<ExtArgs> | null
    /**
     * The data used to create many Roles.
     */
    data: RoleCreateManyInput | RoleCreateManyInput[]
    skipDuplicates?: boolean
  }

  /**
   * Role update
   */
  export type RoleUpdateArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Role
     */
    select?: RoleSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Role
     */
    omit?: RoleOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: RoleInclude<ExtArgs> | null
    /**
     * The data needed to update a Role.
     */
    data: XOR<RoleUpdateInput, RoleUncheckedUpdateInput>
    /**
     * Choose, which Role to update.
     */
    where: RoleWhereUniqueInput
  }

  /**
   * Role updateMany
   */
  export type RoleUpdateManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * The data used to update Roles.
     */
    data: XOR<RoleUpdateManyMutationInput, RoleUncheckedUpdateManyInput>
    /**
     * Filter which Roles to update
     */
    where?: RoleWhereInput
    /**
     * Limit how many Roles to update.
     */
    limit?: number
  }

  /**
   * Role updateManyAndReturn
   */
  export type RoleUpdateManyAndReturnArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Role
     */
    select?: RoleSelectUpdateManyAndReturn<ExtArgs> | null
    /**
     * Omit specific fields from the Role
     */
    omit?: RoleOmit<ExtArgs> | null
    /**
     * The data used to update Roles.
     */
    data: XOR<RoleUpdateManyMutationInput, RoleUncheckedUpdateManyInput>
    /**
     * Filter which Roles to update
     */
    where?: RoleWhereInput
    /**
     * Limit how many Roles to update.
     */
    limit?: number
  }

  /**
   * Role upsert
   */
  export type RoleUpsertArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Role
     */
    select?: RoleSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Role
     */
    omit?: RoleOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: RoleInclude<ExtArgs> | null
    /**
     * The filter to search for the Role to update in case it exists.
     */
    where: RoleWhereUniqueInput
    /**
     * In case the Role found by the `where` argument doesn't exist, create a new Role with this data.
     */
    create: XOR<RoleCreateInput, RoleUncheckedCreateInput>
    /**
     * In case the Role was found with the provided `where` argument, update it with this data.
     */
    update: XOR<RoleUpdateInput, RoleUncheckedUpdateInput>
  }

  /**
   * Role delete
   */
  export type RoleDeleteArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Role
     */
    select?: RoleSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Role
     */
    omit?: RoleOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: RoleInclude<ExtArgs> | null
    /**
     * Filter which Role to delete.
     */
    where: RoleWhereUniqueInput
  }

  /**
   * Role deleteMany
   */
  export type RoleDeleteManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Filter which Roles to delete
     */
    where?: RoleWhereInput
    /**
     * Limit how many Roles to delete.
     */
    limit?: number
  }

  /**
   * Role.userRoles
   */
  export type Role$userRolesArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the UserRole
     */
    select?: UserRoleSelect<ExtArgs> | null
    /**
     * Omit specific fields from the UserRole
     */
    omit?: UserRoleOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: UserRoleInclude<ExtArgs> | null
    where?: UserRoleWhereInput
    orderBy?: UserRoleOrderByWithRelationInput | UserRoleOrderByWithRelationInput[]
    cursor?: UserRoleWhereUniqueInput
    take?: number
    skip?: number
    distinct?: UserRoleScalarFieldEnum | UserRoleScalarFieldEnum[]
  }

  /**
   * Role without action
   */
  export type RoleDefaultArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Role
     */
    select?: RoleSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Role
     */
    omit?: RoleOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: RoleInclude<ExtArgs> | null
  }


  /**
   * Model User
   */

  export type AggregateUser = {
    _count: UserCountAggregateOutputType | null
    _min: UserMinAggregateOutputType | null
    _max: UserMaxAggregateOutputType | null
  }

  export type UserMinAggregateOutputType = {
    id: string | null
    discordId: string | null
    discordUsername: string | null
    nickname: string | null
    avatarUrl: string | null
    createdAt: Date | null
    updatedAt: Date | null
  }

  export type UserMaxAggregateOutputType = {
    id: string | null
    discordId: string | null
    discordUsername: string | null
    nickname: string | null
    avatarUrl: string | null
    createdAt: Date | null
    updatedAt: Date | null
  }

  export type UserCountAggregateOutputType = {
    id: number
    discordId: number
    discordUsername: number
    nickname: number
    avatarUrl: number
    createdAt: number
    updatedAt: number
    _all: number
  }


  export type UserMinAggregateInputType = {
    id?: true
    discordId?: true
    discordUsername?: true
    nickname?: true
    avatarUrl?: true
    createdAt?: true
    updatedAt?: true
  }

  export type UserMaxAggregateInputType = {
    id?: true
    discordId?: true
    discordUsername?: true
    nickname?: true
    avatarUrl?: true
    createdAt?: true
    updatedAt?: true
  }

  export type UserCountAggregateInputType = {
    id?: true
    discordId?: true
    discordUsername?: true
    nickname?: true
    avatarUrl?: true
    createdAt?: true
    updatedAt?: true
    _all?: true
  }

  export type UserAggregateArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Filter which User to aggregate.
     */
    where?: UserWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of Users to fetch.
     */
    orderBy?: UserOrderByWithRelationInput | UserOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the start position
     */
    cursor?: UserWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` Users from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` Users.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Count returned Users
    **/
    _count?: true | UserCountAggregateInputType
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Select which fields to find the minimum value
    **/
    _min?: UserMinAggregateInputType
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Select which fields to find the maximum value
    **/
    _max?: UserMaxAggregateInputType
  }

  export type GetUserAggregateType<T extends UserAggregateArgs> = {
        [P in keyof T & keyof AggregateUser]: P extends '_count' | 'count'
      ? T[P] extends true
        ? number
        : GetScalarType<T[P], AggregateUser[P]>
      : GetScalarType<T[P], AggregateUser[P]>
  }




  export type UserGroupByArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    where?: UserWhereInput
    orderBy?: UserOrderByWithAggregationInput | UserOrderByWithAggregationInput[]
    by: UserScalarFieldEnum[] | UserScalarFieldEnum
    having?: UserScalarWhereWithAggregatesInput
    take?: number
    skip?: number
    _count?: UserCountAggregateInputType | true
    _min?: UserMinAggregateInputType
    _max?: UserMaxAggregateInputType
  }

  export type UserGroupByOutputType = {
    id: string
    discordId: string
    discordUsername: string
    nickname: string | null
    avatarUrl: string | null
    createdAt: Date
    updatedAt: Date
    _count: UserCountAggregateOutputType | null
    _min: UserMinAggregateOutputType | null
    _max: UserMaxAggregateOutputType | null
  }

  type GetUserGroupByPayload<T extends UserGroupByArgs> = Prisma.PrismaPromise<
    Array<
      PickEnumerable<UserGroupByOutputType, T['by']> &
        {
          [P in ((keyof T) & (keyof UserGroupByOutputType))]: P extends '_count'
            ? T[P] extends boolean
              ? number
              : GetScalarType<T[P], UserGroupByOutputType[P]>
            : GetScalarType<T[P], UserGroupByOutputType[P]>
        }
      >
    >


  export type UserSelect<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetSelect<{
    id?: boolean
    discordId?: boolean
    discordUsername?: boolean
    nickname?: boolean
    avatarUrl?: boolean
    createdAt?: boolean
    updatedAt?: boolean
    userRoles?: boolean | User$userRolesArgs<ExtArgs>
    userPermissions?: boolean | User$userPermissionsArgs<ExtArgs>
    boardAuthors?: boolean | User$boardAuthorsArgs<ExtArgs>
    playerBoards?: boolean | User$playerBoardsArgs<ExtArgs>
    teamMembers?: boolean | User$teamMembersArgs<ExtArgs>
    _count?: boolean | UserCountOutputTypeDefaultArgs<ExtArgs>
  }, ExtArgs["result"]["user"]>

  export type UserSelectCreateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetSelect<{
    id?: boolean
    discordId?: boolean
    discordUsername?: boolean
    nickname?: boolean
    avatarUrl?: boolean
    createdAt?: boolean
    updatedAt?: boolean
  }, ExtArgs["result"]["user"]>

  export type UserSelectUpdateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetSelect<{
    id?: boolean
    discordId?: boolean
    discordUsername?: boolean
    nickname?: boolean
    avatarUrl?: boolean
    createdAt?: boolean
    updatedAt?: boolean
  }, ExtArgs["result"]["user"]>

  export type UserSelectScalar = {
    id?: boolean
    discordId?: boolean
    discordUsername?: boolean
    nickname?: boolean
    avatarUrl?: boolean
    createdAt?: boolean
    updatedAt?: boolean
  }

  export type UserOmit<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetOmit<"id" | "discordId" | "discordUsername" | "nickname" | "avatarUrl" | "createdAt" | "updatedAt", ExtArgs["result"]["user"]>
  export type UserInclude<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    userRoles?: boolean | User$userRolesArgs<ExtArgs>
    userPermissions?: boolean | User$userPermissionsArgs<ExtArgs>
    boardAuthors?: boolean | User$boardAuthorsArgs<ExtArgs>
    playerBoards?: boolean | User$playerBoardsArgs<ExtArgs>
    teamMembers?: boolean | User$teamMembersArgs<ExtArgs>
    _count?: boolean | UserCountOutputTypeDefaultArgs<ExtArgs>
  }
  export type UserIncludeCreateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {}
  export type UserIncludeUpdateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {}

  export type $UserPayload<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    name: "User"
    objects: {
      userRoles: Prisma.$UserRolePayload<ExtArgs>[]
      userPermissions: Prisma.$UserPermissionPayload<ExtArgs>[]
      boardAuthors: Prisma.$BoardAuthorPayload<ExtArgs>[]
      playerBoards: Prisma.$PlayerBoardPayload<ExtArgs>[]
      teamMembers: Prisma.$TeamMemberPayload<ExtArgs>[]
    }
    scalars: $Extensions.GetPayloadResult<{
      id: string
      discordId: string
      discordUsername: string
      nickname: string | null
      avatarUrl: string | null
      createdAt: Date
      updatedAt: Date
    }, ExtArgs["result"]["user"]>
    composites: {}
  }

  type UserGetPayload<S extends boolean | null | undefined | UserDefaultArgs> = $Result.GetResult<Prisma.$UserPayload, S>

  type UserCountArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> =
    Omit<UserFindManyArgs, 'select' | 'include' | 'distinct' | 'omit'> & {
      select?: UserCountAggregateInputType | true
    }

  export interface UserDelegate<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs, GlobalOmitOptions = {}> {
    [K: symbol]: { types: Prisma.TypeMap<ExtArgs>['model']['User'], meta: { name: 'User' } }
    /**
     * Find zero or one User that matches the filter.
     * @param {UserFindUniqueArgs} args - Arguments to find a User
     * @example
     * // Get one User
     * const user = await prisma.user.findUnique({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findUnique<T extends UserFindUniqueArgs>(args: SelectSubset<T, UserFindUniqueArgs<ExtArgs>>): Prisma__UserClient<$Result.GetResult<Prisma.$UserPayload<ExtArgs>, T, "findUnique", GlobalOmitOptions> | null, null, ExtArgs, GlobalOmitOptions>

    /**
     * Find one User that matches the filter or throw an error with `error.code='P2025'`
     * if no matches were found.
     * @param {UserFindUniqueOrThrowArgs} args - Arguments to find a User
     * @example
     * // Get one User
     * const user = await prisma.user.findUniqueOrThrow({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findUniqueOrThrow<T extends UserFindUniqueOrThrowArgs>(args: SelectSubset<T, UserFindUniqueOrThrowArgs<ExtArgs>>): Prisma__UserClient<$Result.GetResult<Prisma.$UserPayload<ExtArgs>, T, "findUniqueOrThrow", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Find the first User that matches the filter.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {UserFindFirstArgs} args - Arguments to find a User
     * @example
     * // Get one User
     * const user = await prisma.user.findFirst({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findFirst<T extends UserFindFirstArgs>(args?: SelectSubset<T, UserFindFirstArgs<ExtArgs>>): Prisma__UserClient<$Result.GetResult<Prisma.$UserPayload<ExtArgs>, T, "findFirst", GlobalOmitOptions> | null, null, ExtArgs, GlobalOmitOptions>

    /**
     * Find the first User that matches the filter or
     * throw `PrismaKnownClientError` with `P2025` code if no matches were found.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {UserFindFirstOrThrowArgs} args - Arguments to find a User
     * @example
     * // Get one User
     * const user = await prisma.user.findFirstOrThrow({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findFirstOrThrow<T extends UserFindFirstOrThrowArgs>(args?: SelectSubset<T, UserFindFirstOrThrowArgs<ExtArgs>>): Prisma__UserClient<$Result.GetResult<Prisma.$UserPayload<ExtArgs>, T, "findFirstOrThrow", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Find zero or more Users that matches the filter.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {UserFindManyArgs} args - Arguments to filter and select certain fields only.
     * @example
     * // Get all Users
     * const users = await prisma.user.findMany()
     * 
     * // Get first 10 Users
     * const users = await prisma.user.findMany({ take: 10 })
     * 
     * // Only select the `id`
     * const userWithIdOnly = await prisma.user.findMany({ select: { id: true } })
     * 
     */
    findMany<T extends UserFindManyArgs>(args?: SelectSubset<T, UserFindManyArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$UserPayload<ExtArgs>, T, "findMany", GlobalOmitOptions>>

    /**
     * Create a User.
     * @param {UserCreateArgs} args - Arguments to create a User.
     * @example
     * // Create one User
     * const User = await prisma.user.create({
     *   data: {
     *     // ... data to create a User
     *   }
     * })
     * 
     */
    create<T extends UserCreateArgs>(args: SelectSubset<T, UserCreateArgs<ExtArgs>>): Prisma__UserClient<$Result.GetResult<Prisma.$UserPayload<ExtArgs>, T, "create", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Create many Users.
     * @param {UserCreateManyArgs} args - Arguments to create many Users.
     * @example
     * // Create many Users
     * const user = await prisma.user.createMany({
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     *     
     */
    createMany<T extends UserCreateManyArgs>(args?: SelectSubset<T, UserCreateManyArgs<ExtArgs>>): Prisma.PrismaPromise<BatchPayload>

    /**
     * Create many Users and returns the data saved in the database.
     * @param {UserCreateManyAndReturnArgs} args - Arguments to create many Users.
     * @example
     * // Create many Users
     * const user = await prisma.user.createManyAndReturn({
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * 
     * // Create many Users and only return the `id`
     * const userWithIdOnly = await prisma.user.createManyAndReturn({
     *   select: { id: true },
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * 
     */
    createManyAndReturn<T extends UserCreateManyAndReturnArgs>(args?: SelectSubset<T, UserCreateManyAndReturnArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$UserPayload<ExtArgs>, T, "createManyAndReturn", GlobalOmitOptions>>

    /**
     * Delete a User.
     * @param {UserDeleteArgs} args - Arguments to delete one User.
     * @example
     * // Delete one User
     * const User = await prisma.user.delete({
     *   where: {
     *     // ... filter to delete one User
     *   }
     * })
     * 
     */
    delete<T extends UserDeleteArgs>(args: SelectSubset<T, UserDeleteArgs<ExtArgs>>): Prisma__UserClient<$Result.GetResult<Prisma.$UserPayload<ExtArgs>, T, "delete", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Update one User.
     * @param {UserUpdateArgs} args - Arguments to update one User.
     * @example
     * // Update one User
     * const user = await prisma.user.update({
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: {
     *     // ... provide data here
     *   }
     * })
     * 
     */
    update<T extends UserUpdateArgs>(args: SelectSubset<T, UserUpdateArgs<ExtArgs>>): Prisma__UserClient<$Result.GetResult<Prisma.$UserPayload<ExtArgs>, T, "update", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Delete zero or more Users.
     * @param {UserDeleteManyArgs} args - Arguments to filter Users to delete.
     * @example
     * // Delete a few Users
     * const { count } = await prisma.user.deleteMany({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     * 
     */
    deleteMany<T extends UserDeleteManyArgs>(args?: SelectSubset<T, UserDeleteManyArgs<ExtArgs>>): Prisma.PrismaPromise<BatchPayload>

    /**
     * Update zero or more Users.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {UserUpdateManyArgs} args - Arguments to update one or more rows.
     * @example
     * // Update many Users
     * const user = await prisma.user.updateMany({
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: {
     *     // ... provide data here
     *   }
     * })
     * 
     */
    updateMany<T extends UserUpdateManyArgs>(args: SelectSubset<T, UserUpdateManyArgs<ExtArgs>>): Prisma.PrismaPromise<BatchPayload>

    /**
     * Update zero or more Users and returns the data updated in the database.
     * @param {UserUpdateManyAndReturnArgs} args - Arguments to update many Users.
     * @example
     * // Update many Users
     * const user = await prisma.user.updateManyAndReturn({
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * 
     * // Update zero or more Users and only return the `id`
     * const userWithIdOnly = await prisma.user.updateManyAndReturn({
     *   select: { id: true },
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * 
     */
    updateManyAndReturn<T extends UserUpdateManyAndReturnArgs>(args: SelectSubset<T, UserUpdateManyAndReturnArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$UserPayload<ExtArgs>, T, "updateManyAndReturn", GlobalOmitOptions>>

    /**
     * Create or update one User.
     * @param {UserUpsertArgs} args - Arguments to update or create a User.
     * @example
     * // Update or create a User
     * const user = await prisma.user.upsert({
     *   create: {
     *     // ... data to create a User
     *   },
     *   update: {
     *     // ... in case it already exists, update
     *   },
     *   where: {
     *     // ... the filter for the User we want to update
     *   }
     * })
     */
    upsert<T extends UserUpsertArgs>(args: SelectSubset<T, UserUpsertArgs<ExtArgs>>): Prisma__UserClient<$Result.GetResult<Prisma.$UserPayload<ExtArgs>, T, "upsert", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>


    /**
     * Count the number of Users.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {UserCountArgs} args - Arguments to filter Users to count.
     * @example
     * // Count the number of Users
     * const count = await prisma.user.count({
     *   where: {
     *     // ... the filter for the Users we want to count
     *   }
     * })
    **/
    count<T extends UserCountArgs>(
      args?: Subset<T, UserCountArgs>,
    ): Prisma.PrismaPromise<
      T extends $Utils.Record<'select', any>
        ? T['select'] extends true
          ? number
          : GetScalarType<T['select'], UserCountAggregateOutputType>
        : number
    >

    /**
     * Allows you to perform aggregations operations on a User.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {UserAggregateArgs} args - Select which aggregations you would like to apply and on what fields.
     * @example
     * // Ordered by age ascending
     * // Where email contains prisma.io
     * // Limited to the 10 users
     * const aggregations = await prisma.user.aggregate({
     *   _avg: {
     *     age: true,
     *   },
     *   where: {
     *     email: {
     *       contains: "prisma.io",
     *     },
     *   },
     *   orderBy: {
     *     age: "asc",
     *   },
     *   take: 10,
     * })
    **/
    aggregate<T extends UserAggregateArgs>(args: Subset<T, UserAggregateArgs>): Prisma.PrismaPromise<GetUserAggregateType<T>>

    /**
     * Group by User.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {UserGroupByArgs} args - Group by arguments.
     * @example
     * // Group by city, order by createdAt, get count
     * const result = await prisma.user.groupBy({
     *   by: ['city', 'createdAt'],
     *   orderBy: {
     *     createdAt: true
     *   },
     *   _count: {
     *     _all: true
     *   },
     * })
     * 
    **/
    groupBy<
      T extends UserGroupByArgs,
      HasSelectOrTake extends Or<
        Extends<'skip', Keys<T>>,
        Extends<'take', Keys<T>>
      >,
      OrderByArg extends True extends HasSelectOrTake
        ? { orderBy: UserGroupByArgs['orderBy'] }
        : { orderBy?: UserGroupByArgs['orderBy'] },
      OrderFields extends ExcludeUnderscoreKeys<Keys<MaybeTupleToUnion<T['orderBy']>>>,
      ByFields extends MaybeTupleToUnion<T['by']>,
      ByValid extends Has<ByFields, OrderFields>,
      HavingFields extends GetHavingFields<T['having']>,
      HavingValid extends Has<ByFields, HavingFields>,
      ByEmpty extends T['by'] extends never[] ? True : False,
      InputErrors extends ByEmpty extends True
      ? `Error: "by" must not be empty.`
      : HavingValid extends False
      ? {
          [P in HavingFields]: P extends ByFields
            ? never
            : P extends string
            ? `Error: Field "${P}" used in "having" needs to be provided in "by".`
            : [
                Error,
                'Field ',
                P,
                ` in "having" needs to be provided in "by"`,
              ]
        }[HavingFields]
      : 'take' extends Keys<T>
      ? 'orderBy' extends Keys<T>
        ? ByValid extends True
          ? {}
          : {
              [P in OrderFields]: P extends ByFields
                ? never
                : `Error: Field "${P}" in "orderBy" needs to be provided in "by"`
            }[OrderFields]
        : 'Error: If you provide "take", you also need to provide "orderBy"'
      : 'skip' extends Keys<T>
      ? 'orderBy' extends Keys<T>
        ? ByValid extends True
          ? {}
          : {
              [P in OrderFields]: P extends ByFields
                ? never
                : `Error: Field "${P}" in "orderBy" needs to be provided in "by"`
            }[OrderFields]
        : 'Error: If you provide "skip", you also need to provide "orderBy"'
      : ByValid extends True
      ? {}
      : {
          [P in OrderFields]: P extends ByFields
            ? never
            : `Error: Field "${P}" in "orderBy" needs to be provided in "by"`
        }[OrderFields]
    >(args: SubsetIntersection<T, UserGroupByArgs, OrderByArg> & InputErrors): {} extends InputErrors ? GetUserGroupByPayload<T> : Prisma.PrismaPromise<InputErrors>
  /**
   * Fields of the User model
   */
  readonly fields: UserFieldRefs;
  }

  /**
   * The delegate class that acts as a "Promise-like" for User.
   * Why is this prefixed with `Prisma__`?
   * Because we want to prevent naming conflicts as mentioned in
   * https://github.com/prisma/prisma-client-js/issues/707
   */
  export interface Prisma__UserClient<T, Null = never, ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs, GlobalOmitOptions = {}> extends Prisma.PrismaPromise<T> {
    readonly [Symbol.toStringTag]: "PrismaPromise"
    userRoles<T extends User$userRolesArgs<ExtArgs> = {}>(args?: Subset<T, User$userRolesArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$UserRolePayload<ExtArgs>, T, "findMany", GlobalOmitOptions> | Null>
    userPermissions<T extends User$userPermissionsArgs<ExtArgs> = {}>(args?: Subset<T, User$userPermissionsArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$UserPermissionPayload<ExtArgs>, T, "findMany", GlobalOmitOptions> | Null>
    boardAuthors<T extends User$boardAuthorsArgs<ExtArgs> = {}>(args?: Subset<T, User$boardAuthorsArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$BoardAuthorPayload<ExtArgs>, T, "findMany", GlobalOmitOptions> | Null>
    playerBoards<T extends User$playerBoardsArgs<ExtArgs> = {}>(args?: Subset<T, User$playerBoardsArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$PlayerBoardPayload<ExtArgs>, T, "findMany", GlobalOmitOptions> | Null>
    teamMembers<T extends User$teamMembersArgs<ExtArgs> = {}>(args?: Subset<T, User$teamMembersArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$TeamMemberPayload<ExtArgs>, T, "findMany", GlobalOmitOptions> | Null>
    /**
     * Attaches callbacks for the resolution and/or rejection of the Promise.
     * @param onfulfilled The callback to execute when the Promise is resolved.
     * @param onrejected The callback to execute when the Promise is rejected.
     * @returns A Promise for the completion of which ever callback is executed.
     */
    then<TResult1 = T, TResult2 = never>(onfulfilled?: ((value: T) => TResult1 | PromiseLike<TResult1>) | undefined | null, onrejected?: ((reason: any) => TResult2 | PromiseLike<TResult2>) | undefined | null): $Utils.JsPromise<TResult1 | TResult2>
    /**
     * Attaches a callback for only the rejection of the Promise.
     * @param onrejected The callback to execute when the Promise is rejected.
     * @returns A Promise for the completion of the callback.
     */
    catch<TResult = never>(onrejected?: ((reason: any) => TResult | PromiseLike<TResult>) | undefined | null): $Utils.JsPromise<T | TResult>
    /**
     * Attaches a callback that is invoked when the Promise is settled (fulfilled or rejected). The
     * resolved value cannot be modified from the callback.
     * @param onfinally The callback to execute when the Promise is settled (fulfilled or rejected).
     * @returns A Promise for the completion of the callback.
     */
    finally(onfinally?: (() => void) | undefined | null): $Utils.JsPromise<T>
  }




  /**
   * Fields of the User model
   */
  interface UserFieldRefs {
    readonly id: FieldRef<"User", 'String'>
    readonly discordId: FieldRef<"User", 'String'>
    readonly discordUsername: FieldRef<"User", 'String'>
    readonly nickname: FieldRef<"User", 'String'>
    readonly avatarUrl: FieldRef<"User", 'String'>
    readonly createdAt: FieldRef<"User", 'DateTime'>
    readonly updatedAt: FieldRef<"User", 'DateTime'>
  }
    

  // Custom InputTypes
  /**
   * User findUnique
   */
  export type UserFindUniqueArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the User
     */
    select?: UserSelect<ExtArgs> | null
    /**
     * Omit specific fields from the User
     */
    omit?: UserOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: UserInclude<ExtArgs> | null
    /**
     * Filter, which User to fetch.
     */
    where: UserWhereUniqueInput
  }

  /**
   * User findUniqueOrThrow
   */
  export type UserFindUniqueOrThrowArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the User
     */
    select?: UserSelect<ExtArgs> | null
    /**
     * Omit specific fields from the User
     */
    omit?: UserOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: UserInclude<ExtArgs> | null
    /**
     * Filter, which User to fetch.
     */
    where: UserWhereUniqueInput
  }

  /**
   * User findFirst
   */
  export type UserFindFirstArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the User
     */
    select?: UserSelect<ExtArgs> | null
    /**
     * Omit specific fields from the User
     */
    omit?: UserOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: UserInclude<ExtArgs> | null
    /**
     * Filter, which User to fetch.
     */
    where?: UserWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of Users to fetch.
     */
    orderBy?: UserOrderByWithRelationInput | UserOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the position for searching for Users.
     */
    cursor?: UserWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` Users from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` Users.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/distinct Distinct Docs}
     * 
     * Filter by unique combinations of Users.
     */
    distinct?: UserScalarFieldEnum | UserScalarFieldEnum[]
  }

  /**
   * User findFirstOrThrow
   */
  export type UserFindFirstOrThrowArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the User
     */
    select?: UserSelect<ExtArgs> | null
    /**
     * Omit specific fields from the User
     */
    omit?: UserOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: UserInclude<ExtArgs> | null
    /**
     * Filter, which User to fetch.
     */
    where?: UserWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of Users to fetch.
     */
    orderBy?: UserOrderByWithRelationInput | UserOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the position for searching for Users.
     */
    cursor?: UserWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` Users from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` Users.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/distinct Distinct Docs}
     * 
     * Filter by unique combinations of Users.
     */
    distinct?: UserScalarFieldEnum | UserScalarFieldEnum[]
  }

  /**
   * User findMany
   */
  export type UserFindManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the User
     */
    select?: UserSelect<ExtArgs> | null
    /**
     * Omit specific fields from the User
     */
    omit?: UserOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: UserInclude<ExtArgs> | null
    /**
     * Filter, which Users to fetch.
     */
    where?: UserWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of Users to fetch.
     */
    orderBy?: UserOrderByWithRelationInput | UserOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the position for listing Users.
     */
    cursor?: UserWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` Users from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` Users.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/distinct Distinct Docs}
     * 
     * Filter by unique combinations of Users.
     */
    distinct?: UserScalarFieldEnum | UserScalarFieldEnum[]
  }

  /**
   * User create
   */
  export type UserCreateArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the User
     */
    select?: UserSelect<ExtArgs> | null
    /**
     * Omit specific fields from the User
     */
    omit?: UserOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: UserInclude<ExtArgs> | null
    /**
     * The data needed to create a User.
     */
    data: XOR<UserCreateInput, UserUncheckedCreateInput>
  }

  /**
   * User createMany
   */
  export type UserCreateManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * The data used to create many Users.
     */
    data: UserCreateManyInput | UserCreateManyInput[]
    skipDuplicates?: boolean
  }

  /**
   * User createManyAndReturn
   */
  export type UserCreateManyAndReturnArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the User
     */
    select?: UserSelectCreateManyAndReturn<ExtArgs> | null
    /**
     * Omit specific fields from the User
     */
    omit?: UserOmit<ExtArgs> | null
    /**
     * The data used to create many Users.
     */
    data: UserCreateManyInput | UserCreateManyInput[]
    skipDuplicates?: boolean
  }

  /**
   * User update
   */
  export type UserUpdateArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the User
     */
    select?: UserSelect<ExtArgs> | null
    /**
     * Omit specific fields from the User
     */
    omit?: UserOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: UserInclude<ExtArgs> | null
    /**
     * The data needed to update a User.
     */
    data: XOR<UserUpdateInput, UserUncheckedUpdateInput>
    /**
     * Choose, which User to update.
     */
    where: UserWhereUniqueInput
  }

  /**
   * User updateMany
   */
  export type UserUpdateManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * The data used to update Users.
     */
    data: XOR<UserUpdateManyMutationInput, UserUncheckedUpdateManyInput>
    /**
     * Filter which Users to update
     */
    where?: UserWhereInput
    /**
     * Limit how many Users to update.
     */
    limit?: number
  }

  /**
   * User updateManyAndReturn
   */
  export type UserUpdateManyAndReturnArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the User
     */
    select?: UserSelectUpdateManyAndReturn<ExtArgs> | null
    /**
     * Omit specific fields from the User
     */
    omit?: UserOmit<ExtArgs> | null
    /**
     * The data used to update Users.
     */
    data: XOR<UserUpdateManyMutationInput, UserUncheckedUpdateManyInput>
    /**
     * Filter which Users to update
     */
    where?: UserWhereInput
    /**
     * Limit how many Users to update.
     */
    limit?: number
  }

  /**
   * User upsert
   */
  export type UserUpsertArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the User
     */
    select?: UserSelect<ExtArgs> | null
    /**
     * Omit specific fields from the User
     */
    omit?: UserOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: UserInclude<ExtArgs> | null
    /**
     * The filter to search for the User to update in case it exists.
     */
    where: UserWhereUniqueInput
    /**
     * In case the User found by the `where` argument doesn't exist, create a new User with this data.
     */
    create: XOR<UserCreateInput, UserUncheckedCreateInput>
    /**
     * In case the User was found with the provided `where` argument, update it with this data.
     */
    update: XOR<UserUpdateInput, UserUncheckedUpdateInput>
  }

  /**
   * User delete
   */
  export type UserDeleteArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the User
     */
    select?: UserSelect<ExtArgs> | null
    /**
     * Omit specific fields from the User
     */
    omit?: UserOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: UserInclude<ExtArgs> | null
    /**
     * Filter which User to delete.
     */
    where: UserWhereUniqueInput
  }

  /**
   * User deleteMany
   */
  export type UserDeleteManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Filter which Users to delete
     */
    where?: UserWhereInput
    /**
     * Limit how many Users to delete.
     */
    limit?: number
  }

  /**
   * User.userRoles
   */
  export type User$userRolesArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the UserRole
     */
    select?: UserRoleSelect<ExtArgs> | null
    /**
     * Omit specific fields from the UserRole
     */
    omit?: UserRoleOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: UserRoleInclude<ExtArgs> | null
    where?: UserRoleWhereInput
    orderBy?: UserRoleOrderByWithRelationInput | UserRoleOrderByWithRelationInput[]
    cursor?: UserRoleWhereUniqueInput
    take?: number
    skip?: number
    distinct?: UserRoleScalarFieldEnum | UserRoleScalarFieldEnum[]
  }

  /**
   * User.userPermissions
   */
  export type User$userPermissionsArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the UserPermission
     */
    select?: UserPermissionSelect<ExtArgs> | null
    /**
     * Omit specific fields from the UserPermission
     */
    omit?: UserPermissionOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: UserPermissionInclude<ExtArgs> | null
    where?: UserPermissionWhereInput
    orderBy?: UserPermissionOrderByWithRelationInput | UserPermissionOrderByWithRelationInput[]
    cursor?: UserPermissionWhereUniqueInput
    take?: number
    skip?: number
    distinct?: UserPermissionScalarFieldEnum | UserPermissionScalarFieldEnum[]
  }

  /**
   * User.boardAuthors
   */
  export type User$boardAuthorsArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the BoardAuthor
     */
    select?: BoardAuthorSelect<ExtArgs> | null
    /**
     * Omit specific fields from the BoardAuthor
     */
    omit?: BoardAuthorOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: BoardAuthorInclude<ExtArgs> | null
    where?: BoardAuthorWhereInput
    orderBy?: BoardAuthorOrderByWithRelationInput | BoardAuthorOrderByWithRelationInput[]
    cursor?: BoardAuthorWhereUniqueInput
    take?: number
    skip?: number
    distinct?: BoardAuthorScalarFieldEnum | BoardAuthorScalarFieldEnum[]
  }

  /**
   * User.playerBoards
   */
  export type User$playerBoardsArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the PlayerBoard
     */
    select?: PlayerBoardSelect<ExtArgs> | null
    /**
     * Omit specific fields from the PlayerBoard
     */
    omit?: PlayerBoardOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: PlayerBoardInclude<ExtArgs> | null
    where?: PlayerBoardWhereInput
    orderBy?: PlayerBoardOrderByWithRelationInput | PlayerBoardOrderByWithRelationInput[]
    cursor?: PlayerBoardWhereUniqueInput
    take?: number
    skip?: number
    distinct?: PlayerBoardScalarFieldEnum | PlayerBoardScalarFieldEnum[]
  }

  /**
   * User.teamMembers
   */
  export type User$teamMembersArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the TeamMember
     */
    select?: TeamMemberSelect<ExtArgs> | null
    /**
     * Omit specific fields from the TeamMember
     */
    omit?: TeamMemberOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TeamMemberInclude<ExtArgs> | null
    where?: TeamMemberWhereInput
    orderBy?: TeamMemberOrderByWithRelationInput | TeamMemberOrderByWithRelationInput[]
    cursor?: TeamMemberWhereUniqueInput
    take?: number
    skip?: number
    distinct?: TeamMemberScalarFieldEnum | TeamMemberScalarFieldEnum[]
  }

  /**
   * User without action
   */
  export type UserDefaultArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the User
     */
    select?: UserSelect<ExtArgs> | null
    /**
     * Omit specific fields from the User
     */
    omit?: UserOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: UserInclude<ExtArgs> | null
  }


  /**
   * Model UserPermission
   */

  export type AggregateUserPermission = {
    _count: UserPermissionCountAggregateOutputType | null
    _min: UserPermissionMinAggregateOutputType | null
    _max: UserPermissionMaxAggregateOutputType | null
  }

  export type UserPermissionMinAggregateOutputType = {
    id: string | null
    userId: string | null
    permissionKey: string | null
    createdAt: Date | null
  }

  export type UserPermissionMaxAggregateOutputType = {
    id: string | null
    userId: string | null
    permissionKey: string | null
    createdAt: Date | null
  }

  export type UserPermissionCountAggregateOutputType = {
    id: number
    userId: number
    permissionKey: number
    createdAt: number
    _all: number
  }


  export type UserPermissionMinAggregateInputType = {
    id?: true
    userId?: true
    permissionKey?: true
    createdAt?: true
  }

  export type UserPermissionMaxAggregateInputType = {
    id?: true
    userId?: true
    permissionKey?: true
    createdAt?: true
  }

  export type UserPermissionCountAggregateInputType = {
    id?: true
    userId?: true
    permissionKey?: true
    createdAt?: true
    _all?: true
  }

  export type UserPermissionAggregateArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Filter which UserPermission to aggregate.
     */
    where?: UserPermissionWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of UserPermissions to fetch.
     */
    orderBy?: UserPermissionOrderByWithRelationInput | UserPermissionOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the start position
     */
    cursor?: UserPermissionWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` UserPermissions from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` UserPermissions.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Count returned UserPermissions
    **/
    _count?: true | UserPermissionCountAggregateInputType
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Select which fields to find the minimum value
    **/
    _min?: UserPermissionMinAggregateInputType
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Select which fields to find the maximum value
    **/
    _max?: UserPermissionMaxAggregateInputType
  }

  export type GetUserPermissionAggregateType<T extends UserPermissionAggregateArgs> = {
        [P in keyof T & keyof AggregateUserPermission]: P extends '_count' | 'count'
      ? T[P] extends true
        ? number
        : GetScalarType<T[P], AggregateUserPermission[P]>
      : GetScalarType<T[P], AggregateUserPermission[P]>
  }




  export type UserPermissionGroupByArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    where?: UserPermissionWhereInput
    orderBy?: UserPermissionOrderByWithAggregationInput | UserPermissionOrderByWithAggregationInput[]
    by: UserPermissionScalarFieldEnum[] | UserPermissionScalarFieldEnum
    having?: UserPermissionScalarWhereWithAggregatesInput
    take?: number
    skip?: number
    _count?: UserPermissionCountAggregateInputType | true
    _min?: UserPermissionMinAggregateInputType
    _max?: UserPermissionMaxAggregateInputType
  }

  export type UserPermissionGroupByOutputType = {
    id: string
    userId: string
    permissionKey: string
    createdAt: Date
    _count: UserPermissionCountAggregateOutputType | null
    _min: UserPermissionMinAggregateOutputType | null
    _max: UserPermissionMaxAggregateOutputType | null
  }

  type GetUserPermissionGroupByPayload<T extends UserPermissionGroupByArgs> = Prisma.PrismaPromise<
    Array<
      PickEnumerable<UserPermissionGroupByOutputType, T['by']> &
        {
          [P in ((keyof T) & (keyof UserPermissionGroupByOutputType))]: P extends '_count'
            ? T[P] extends boolean
              ? number
              : GetScalarType<T[P], UserPermissionGroupByOutputType[P]>
            : GetScalarType<T[P], UserPermissionGroupByOutputType[P]>
        }
      >
    >


  export type UserPermissionSelect<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetSelect<{
    id?: boolean
    userId?: boolean
    permissionKey?: boolean
    createdAt?: boolean
    user?: boolean | UserDefaultArgs<ExtArgs>
  }, ExtArgs["result"]["userPermission"]>

  export type UserPermissionSelectCreateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetSelect<{
    id?: boolean
    userId?: boolean
    permissionKey?: boolean
    createdAt?: boolean
    user?: boolean | UserDefaultArgs<ExtArgs>
  }, ExtArgs["result"]["userPermission"]>

  export type UserPermissionSelectUpdateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetSelect<{
    id?: boolean
    userId?: boolean
    permissionKey?: boolean
    createdAt?: boolean
    user?: boolean | UserDefaultArgs<ExtArgs>
  }, ExtArgs["result"]["userPermission"]>

  export type UserPermissionSelectScalar = {
    id?: boolean
    userId?: boolean
    permissionKey?: boolean
    createdAt?: boolean
  }

  export type UserPermissionOmit<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetOmit<"id" | "userId" | "permissionKey" | "createdAt", ExtArgs["result"]["userPermission"]>
  export type UserPermissionInclude<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    user?: boolean | UserDefaultArgs<ExtArgs>
  }
  export type UserPermissionIncludeCreateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    user?: boolean | UserDefaultArgs<ExtArgs>
  }
  export type UserPermissionIncludeUpdateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    user?: boolean | UserDefaultArgs<ExtArgs>
  }

  export type $UserPermissionPayload<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    name: "UserPermission"
    objects: {
      user: Prisma.$UserPayload<ExtArgs>
    }
    scalars: $Extensions.GetPayloadResult<{
      id: string
      userId: string
      permissionKey: string
      createdAt: Date
    }, ExtArgs["result"]["userPermission"]>
    composites: {}
  }

  type UserPermissionGetPayload<S extends boolean | null | undefined | UserPermissionDefaultArgs> = $Result.GetResult<Prisma.$UserPermissionPayload, S>

  type UserPermissionCountArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> =
    Omit<UserPermissionFindManyArgs, 'select' | 'include' | 'distinct' | 'omit'> & {
      select?: UserPermissionCountAggregateInputType | true
    }

  export interface UserPermissionDelegate<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs, GlobalOmitOptions = {}> {
    [K: symbol]: { types: Prisma.TypeMap<ExtArgs>['model']['UserPermission'], meta: { name: 'UserPermission' } }
    /**
     * Find zero or one UserPermission that matches the filter.
     * @param {UserPermissionFindUniqueArgs} args - Arguments to find a UserPermission
     * @example
     * // Get one UserPermission
     * const userPermission = await prisma.userPermission.findUnique({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findUnique<T extends UserPermissionFindUniqueArgs>(args: SelectSubset<T, UserPermissionFindUniqueArgs<ExtArgs>>): Prisma__UserPermissionClient<$Result.GetResult<Prisma.$UserPermissionPayload<ExtArgs>, T, "findUnique", GlobalOmitOptions> | null, null, ExtArgs, GlobalOmitOptions>

    /**
     * Find one UserPermission that matches the filter or throw an error with `error.code='P2025'`
     * if no matches were found.
     * @param {UserPermissionFindUniqueOrThrowArgs} args - Arguments to find a UserPermission
     * @example
     * // Get one UserPermission
     * const userPermission = await prisma.userPermission.findUniqueOrThrow({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findUniqueOrThrow<T extends UserPermissionFindUniqueOrThrowArgs>(args: SelectSubset<T, UserPermissionFindUniqueOrThrowArgs<ExtArgs>>): Prisma__UserPermissionClient<$Result.GetResult<Prisma.$UserPermissionPayload<ExtArgs>, T, "findUniqueOrThrow", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Find the first UserPermission that matches the filter.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {UserPermissionFindFirstArgs} args - Arguments to find a UserPermission
     * @example
     * // Get one UserPermission
     * const userPermission = await prisma.userPermission.findFirst({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findFirst<T extends UserPermissionFindFirstArgs>(args?: SelectSubset<T, UserPermissionFindFirstArgs<ExtArgs>>): Prisma__UserPermissionClient<$Result.GetResult<Prisma.$UserPermissionPayload<ExtArgs>, T, "findFirst", GlobalOmitOptions> | null, null, ExtArgs, GlobalOmitOptions>

    /**
     * Find the first UserPermission that matches the filter or
     * throw `PrismaKnownClientError` with `P2025` code if no matches were found.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {UserPermissionFindFirstOrThrowArgs} args - Arguments to find a UserPermission
     * @example
     * // Get one UserPermission
     * const userPermission = await prisma.userPermission.findFirstOrThrow({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findFirstOrThrow<T extends UserPermissionFindFirstOrThrowArgs>(args?: SelectSubset<T, UserPermissionFindFirstOrThrowArgs<ExtArgs>>): Prisma__UserPermissionClient<$Result.GetResult<Prisma.$UserPermissionPayload<ExtArgs>, T, "findFirstOrThrow", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Find zero or more UserPermissions that matches the filter.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {UserPermissionFindManyArgs} args - Arguments to filter and select certain fields only.
     * @example
     * // Get all UserPermissions
     * const userPermissions = await prisma.userPermission.findMany()
     * 
     * // Get first 10 UserPermissions
     * const userPermissions = await prisma.userPermission.findMany({ take: 10 })
     * 
     * // Only select the `id`
     * const userPermissionWithIdOnly = await prisma.userPermission.findMany({ select: { id: true } })
     * 
     */
    findMany<T extends UserPermissionFindManyArgs>(args?: SelectSubset<T, UserPermissionFindManyArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$UserPermissionPayload<ExtArgs>, T, "findMany", GlobalOmitOptions>>

    /**
     * Create a UserPermission.
     * @param {UserPermissionCreateArgs} args - Arguments to create a UserPermission.
     * @example
     * // Create one UserPermission
     * const UserPermission = await prisma.userPermission.create({
     *   data: {
     *     // ... data to create a UserPermission
     *   }
     * })
     * 
     */
    create<T extends UserPermissionCreateArgs>(args: SelectSubset<T, UserPermissionCreateArgs<ExtArgs>>): Prisma__UserPermissionClient<$Result.GetResult<Prisma.$UserPermissionPayload<ExtArgs>, T, "create", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Create many UserPermissions.
     * @param {UserPermissionCreateManyArgs} args - Arguments to create many UserPermissions.
     * @example
     * // Create many UserPermissions
     * const userPermission = await prisma.userPermission.createMany({
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     *     
     */
    createMany<T extends UserPermissionCreateManyArgs>(args?: SelectSubset<T, UserPermissionCreateManyArgs<ExtArgs>>): Prisma.PrismaPromise<BatchPayload>

    /**
     * Create many UserPermissions and returns the data saved in the database.
     * @param {UserPermissionCreateManyAndReturnArgs} args - Arguments to create many UserPermissions.
     * @example
     * // Create many UserPermissions
     * const userPermission = await prisma.userPermission.createManyAndReturn({
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * 
     * // Create many UserPermissions and only return the `id`
     * const userPermissionWithIdOnly = await prisma.userPermission.createManyAndReturn({
     *   select: { id: true },
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * 
     */
    createManyAndReturn<T extends UserPermissionCreateManyAndReturnArgs>(args?: SelectSubset<T, UserPermissionCreateManyAndReturnArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$UserPermissionPayload<ExtArgs>, T, "createManyAndReturn", GlobalOmitOptions>>

    /**
     * Delete a UserPermission.
     * @param {UserPermissionDeleteArgs} args - Arguments to delete one UserPermission.
     * @example
     * // Delete one UserPermission
     * const UserPermission = await prisma.userPermission.delete({
     *   where: {
     *     // ... filter to delete one UserPermission
     *   }
     * })
     * 
     */
    delete<T extends UserPermissionDeleteArgs>(args: SelectSubset<T, UserPermissionDeleteArgs<ExtArgs>>): Prisma__UserPermissionClient<$Result.GetResult<Prisma.$UserPermissionPayload<ExtArgs>, T, "delete", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Update one UserPermission.
     * @param {UserPermissionUpdateArgs} args - Arguments to update one UserPermission.
     * @example
     * // Update one UserPermission
     * const userPermission = await prisma.userPermission.update({
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: {
     *     // ... provide data here
     *   }
     * })
     * 
     */
    update<T extends UserPermissionUpdateArgs>(args: SelectSubset<T, UserPermissionUpdateArgs<ExtArgs>>): Prisma__UserPermissionClient<$Result.GetResult<Prisma.$UserPermissionPayload<ExtArgs>, T, "update", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Delete zero or more UserPermissions.
     * @param {UserPermissionDeleteManyArgs} args - Arguments to filter UserPermissions to delete.
     * @example
     * // Delete a few UserPermissions
     * const { count } = await prisma.userPermission.deleteMany({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     * 
     */
    deleteMany<T extends UserPermissionDeleteManyArgs>(args?: SelectSubset<T, UserPermissionDeleteManyArgs<ExtArgs>>): Prisma.PrismaPromise<BatchPayload>

    /**
     * Update zero or more UserPermissions.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {UserPermissionUpdateManyArgs} args - Arguments to update one or more rows.
     * @example
     * // Update many UserPermissions
     * const userPermission = await prisma.userPermission.updateMany({
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: {
     *     // ... provide data here
     *   }
     * })
     * 
     */
    updateMany<T extends UserPermissionUpdateManyArgs>(args: SelectSubset<T, UserPermissionUpdateManyArgs<ExtArgs>>): Prisma.PrismaPromise<BatchPayload>

    /**
     * Update zero or more UserPermissions and returns the data updated in the database.
     * @param {UserPermissionUpdateManyAndReturnArgs} args - Arguments to update many UserPermissions.
     * @example
     * // Update many UserPermissions
     * const userPermission = await prisma.userPermission.updateManyAndReturn({
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * 
     * // Update zero or more UserPermissions and only return the `id`
     * const userPermissionWithIdOnly = await prisma.userPermission.updateManyAndReturn({
     *   select: { id: true },
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * 
     */
    updateManyAndReturn<T extends UserPermissionUpdateManyAndReturnArgs>(args: SelectSubset<T, UserPermissionUpdateManyAndReturnArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$UserPermissionPayload<ExtArgs>, T, "updateManyAndReturn", GlobalOmitOptions>>

    /**
     * Create or update one UserPermission.
     * @param {UserPermissionUpsertArgs} args - Arguments to update or create a UserPermission.
     * @example
     * // Update or create a UserPermission
     * const userPermission = await prisma.userPermission.upsert({
     *   create: {
     *     // ... data to create a UserPermission
     *   },
     *   update: {
     *     // ... in case it already exists, update
     *   },
     *   where: {
     *     // ... the filter for the UserPermission we want to update
     *   }
     * })
     */
    upsert<T extends UserPermissionUpsertArgs>(args: SelectSubset<T, UserPermissionUpsertArgs<ExtArgs>>): Prisma__UserPermissionClient<$Result.GetResult<Prisma.$UserPermissionPayload<ExtArgs>, T, "upsert", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>


    /**
     * Count the number of UserPermissions.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {UserPermissionCountArgs} args - Arguments to filter UserPermissions to count.
     * @example
     * // Count the number of UserPermissions
     * const count = await prisma.userPermission.count({
     *   where: {
     *     // ... the filter for the UserPermissions we want to count
     *   }
     * })
    **/
    count<T extends UserPermissionCountArgs>(
      args?: Subset<T, UserPermissionCountArgs>,
    ): Prisma.PrismaPromise<
      T extends $Utils.Record<'select', any>
        ? T['select'] extends true
          ? number
          : GetScalarType<T['select'], UserPermissionCountAggregateOutputType>
        : number
    >

    /**
     * Allows you to perform aggregations operations on a UserPermission.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {UserPermissionAggregateArgs} args - Select which aggregations you would like to apply and on what fields.
     * @example
     * // Ordered by age ascending
     * // Where email contains prisma.io
     * // Limited to the 10 users
     * const aggregations = await prisma.user.aggregate({
     *   _avg: {
     *     age: true,
     *   },
     *   where: {
     *     email: {
     *       contains: "prisma.io",
     *     },
     *   },
     *   orderBy: {
     *     age: "asc",
     *   },
     *   take: 10,
     * })
    **/
    aggregate<T extends UserPermissionAggregateArgs>(args: Subset<T, UserPermissionAggregateArgs>): Prisma.PrismaPromise<GetUserPermissionAggregateType<T>>

    /**
     * Group by UserPermission.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {UserPermissionGroupByArgs} args - Group by arguments.
     * @example
     * // Group by city, order by createdAt, get count
     * const result = await prisma.user.groupBy({
     *   by: ['city', 'createdAt'],
     *   orderBy: {
     *     createdAt: true
     *   },
     *   _count: {
     *     _all: true
     *   },
     * })
     * 
    **/
    groupBy<
      T extends UserPermissionGroupByArgs,
      HasSelectOrTake extends Or<
        Extends<'skip', Keys<T>>,
        Extends<'take', Keys<T>>
      >,
      OrderByArg extends True extends HasSelectOrTake
        ? { orderBy: UserPermissionGroupByArgs['orderBy'] }
        : { orderBy?: UserPermissionGroupByArgs['orderBy'] },
      OrderFields extends ExcludeUnderscoreKeys<Keys<MaybeTupleToUnion<T['orderBy']>>>,
      ByFields extends MaybeTupleToUnion<T['by']>,
      ByValid extends Has<ByFields, OrderFields>,
      HavingFields extends GetHavingFields<T['having']>,
      HavingValid extends Has<ByFields, HavingFields>,
      ByEmpty extends T['by'] extends never[] ? True : False,
      InputErrors extends ByEmpty extends True
      ? `Error: "by" must not be empty.`
      : HavingValid extends False
      ? {
          [P in HavingFields]: P extends ByFields
            ? never
            : P extends string
            ? `Error: Field "${P}" used in "having" needs to be provided in "by".`
            : [
                Error,
                'Field ',
                P,
                ` in "having" needs to be provided in "by"`,
              ]
        }[HavingFields]
      : 'take' extends Keys<T>
      ? 'orderBy' extends Keys<T>
        ? ByValid extends True
          ? {}
          : {
              [P in OrderFields]: P extends ByFields
                ? never
                : `Error: Field "${P}" in "orderBy" needs to be provided in "by"`
            }[OrderFields]
        : 'Error: If you provide "take", you also need to provide "orderBy"'
      : 'skip' extends Keys<T>
      ? 'orderBy' extends Keys<T>
        ? ByValid extends True
          ? {}
          : {
              [P in OrderFields]: P extends ByFields
                ? never
                : `Error: Field "${P}" in "orderBy" needs to be provided in "by"`
            }[OrderFields]
        : 'Error: If you provide "skip", you also need to provide "orderBy"'
      : ByValid extends True
      ? {}
      : {
          [P in OrderFields]: P extends ByFields
            ? never
            : `Error: Field "${P}" in "orderBy" needs to be provided in "by"`
        }[OrderFields]
    >(args: SubsetIntersection<T, UserPermissionGroupByArgs, OrderByArg> & InputErrors): {} extends InputErrors ? GetUserPermissionGroupByPayload<T> : Prisma.PrismaPromise<InputErrors>
  /**
   * Fields of the UserPermission model
   */
  readonly fields: UserPermissionFieldRefs;
  }

  /**
   * The delegate class that acts as a "Promise-like" for UserPermission.
   * Why is this prefixed with `Prisma__`?
   * Because we want to prevent naming conflicts as mentioned in
   * https://github.com/prisma/prisma-client-js/issues/707
   */
  export interface Prisma__UserPermissionClient<T, Null = never, ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs, GlobalOmitOptions = {}> extends Prisma.PrismaPromise<T> {
    readonly [Symbol.toStringTag]: "PrismaPromise"
    user<T extends UserDefaultArgs<ExtArgs> = {}>(args?: Subset<T, UserDefaultArgs<ExtArgs>>): Prisma__UserClient<$Result.GetResult<Prisma.$UserPayload<ExtArgs>, T, "findUniqueOrThrow", GlobalOmitOptions> | Null, Null, ExtArgs, GlobalOmitOptions>
    /**
     * Attaches callbacks for the resolution and/or rejection of the Promise.
     * @param onfulfilled The callback to execute when the Promise is resolved.
     * @param onrejected The callback to execute when the Promise is rejected.
     * @returns A Promise for the completion of which ever callback is executed.
     */
    then<TResult1 = T, TResult2 = never>(onfulfilled?: ((value: T) => TResult1 | PromiseLike<TResult1>) | undefined | null, onrejected?: ((reason: any) => TResult2 | PromiseLike<TResult2>) | undefined | null): $Utils.JsPromise<TResult1 | TResult2>
    /**
     * Attaches a callback for only the rejection of the Promise.
     * @param onrejected The callback to execute when the Promise is rejected.
     * @returns A Promise for the completion of the callback.
     */
    catch<TResult = never>(onrejected?: ((reason: any) => TResult | PromiseLike<TResult>) | undefined | null): $Utils.JsPromise<T | TResult>
    /**
     * Attaches a callback that is invoked when the Promise is settled (fulfilled or rejected). The
     * resolved value cannot be modified from the callback.
     * @param onfinally The callback to execute when the Promise is settled (fulfilled or rejected).
     * @returns A Promise for the completion of the callback.
     */
    finally(onfinally?: (() => void) | undefined | null): $Utils.JsPromise<T>
  }




  /**
   * Fields of the UserPermission model
   */
  interface UserPermissionFieldRefs {
    readonly id: FieldRef<"UserPermission", 'String'>
    readonly userId: FieldRef<"UserPermission", 'String'>
    readonly permissionKey: FieldRef<"UserPermission", 'String'>
    readonly createdAt: FieldRef<"UserPermission", 'DateTime'>
  }
    

  // Custom InputTypes
  /**
   * UserPermission findUnique
   */
  export type UserPermissionFindUniqueArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the UserPermission
     */
    select?: UserPermissionSelect<ExtArgs> | null
    /**
     * Omit specific fields from the UserPermission
     */
    omit?: UserPermissionOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: UserPermissionInclude<ExtArgs> | null
    /**
     * Filter, which UserPermission to fetch.
     */
    where: UserPermissionWhereUniqueInput
  }

  /**
   * UserPermission findUniqueOrThrow
   */
  export type UserPermissionFindUniqueOrThrowArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the UserPermission
     */
    select?: UserPermissionSelect<ExtArgs> | null
    /**
     * Omit specific fields from the UserPermission
     */
    omit?: UserPermissionOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: UserPermissionInclude<ExtArgs> | null
    /**
     * Filter, which UserPermission to fetch.
     */
    where: UserPermissionWhereUniqueInput
  }

  /**
   * UserPermission findFirst
   */
  export type UserPermissionFindFirstArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the UserPermission
     */
    select?: UserPermissionSelect<ExtArgs> | null
    /**
     * Omit specific fields from the UserPermission
     */
    omit?: UserPermissionOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: UserPermissionInclude<ExtArgs> | null
    /**
     * Filter, which UserPermission to fetch.
     */
    where?: UserPermissionWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of UserPermissions to fetch.
     */
    orderBy?: UserPermissionOrderByWithRelationInput | UserPermissionOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the position for searching for UserPermissions.
     */
    cursor?: UserPermissionWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` UserPermissions from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` UserPermissions.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/distinct Distinct Docs}
     * 
     * Filter by unique combinations of UserPermissions.
     */
    distinct?: UserPermissionScalarFieldEnum | UserPermissionScalarFieldEnum[]
  }

  /**
   * UserPermission findFirstOrThrow
   */
  export type UserPermissionFindFirstOrThrowArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the UserPermission
     */
    select?: UserPermissionSelect<ExtArgs> | null
    /**
     * Omit specific fields from the UserPermission
     */
    omit?: UserPermissionOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: UserPermissionInclude<ExtArgs> | null
    /**
     * Filter, which UserPermission to fetch.
     */
    where?: UserPermissionWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of UserPermissions to fetch.
     */
    orderBy?: UserPermissionOrderByWithRelationInput | UserPermissionOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the position for searching for UserPermissions.
     */
    cursor?: UserPermissionWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` UserPermissions from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` UserPermissions.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/distinct Distinct Docs}
     * 
     * Filter by unique combinations of UserPermissions.
     */
    distinct?: UserPermissionScalarFieldEnum | UserPermissionScalarFieldEnum[]
  }

  /**
   * UserPermission findMany
   */
  export type UserPermissionFindManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the UserPermission
     */
    select?: UserPermissionSelect<ExtArgs> | null
    /**
     * Omit specific fields from the UserPermission
     */
    omit?: UserPermissionOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: UserPermissionInclude<ExtArgs> | null
    /**
     * Filter, which UserPermissions to fetch.
     */
    where?: UserPermissionWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of UserPermissions to fetch.
     */
    orderBy?: UserPermissionOrderByWithRelationInput | UserPermissionOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the position for listing UserPermissions.
     */
    cursor?: UserPermissionWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` UserPermissions from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` UserPermissions.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/distinct Distinct Docs}
     * 
     * Filter by unique combinations of UserPermissions.
     */
    distinct?: UserPermissionScalarFieldEnum | UserPermissionScalarFieldEnum[]
  }

  /**
   * UserPermission create
   */
  export type UserPermissionCreateArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the UserPermission
     */
    select?: UserPermissionSelect<ExtArgs> | null
    /**
     * Omit specific fields from the UserPermission
     */
    omit?: UserPermissionOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: UserPermissionInclude<ExtArgs> | null
    /**
     * The data needed to create a UserPermission.
     */
    data: XOR<UserPermissionCreateInput, UserPermissionUncheckedCreateInput>
  }

  /**
   * UserPermission createMany
   */
  export type UserPermissionCreateManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * The data used to create many UserPermissions.
     */
    data: UserPermissionCreateManyInput | UserPermissionCreateManyInput[]
    skipDuplicates?: boolean
  }

  /**
   * UserPermission createManyAndReturn
   */
  export type UserPermissionCreateManyAndReturnArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the UserPermission
     */
    select?: UserPermissionSelectCreateManyAndReturn<ExtArgs> | null
    /**
     * Omit specific fields from the UserPermission
     */
    omit?: UserPermissionOmit<ExtArgs> | null
    /**
     * The data used to create many UserPermissions.
     */
    data: UserPermissionCreateManyInput | UserPermissionCreateManyInput[]
    skipDuplicates?: boolean
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: UserPermissionIncludeCreateManyAndReturn<ExtArgs> | null
  }

  /**
   * UserPermission update
   */
  export type UserPermissionUpdateArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the UserPermission
     */
    select?: UserPermissionSelect<ExtArgs> | null
    /**
     * Omit specific fields from the UserPermission
     */
    omit?: UserPermissionOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: UserPermissionInclude<ExtArgs> | null
    /**
     * The data needed to update a UserPermission.
     */
    data: XOR<UserPermissionUpdateInput, UserPermissionUncheckedUpdateInput>
    /**
     * Choose, which UserPermission to update.
     */
    where: UserPermissionWhereUniqueInput
  }

  /**
   * UserPermission updateMany
   */
  export type UserPermissionUpdateManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * The data used to update UserPermissions.
     */
    data: XOR<UserPermissionUpdateManyMutationInput, UserPermissionUncheckedUpdateManyInput>
    /**
     * Filter which UserPermissions to update
     */
    where?: UserPermissionWhereInput
    /**
     * Limit how many UserPermissions to update.
     */
    limit?: number
  }

  /**
   * UserPermission updateManyAndReturn
   */
  export type UserPermissionUpdateManyAndReturnArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the UserPermission
     */
    select?: UserPermissionSelectUpdateManyAndReturn<ExtArgs> | null
    /**
     * Omit specific fields from the UserPermission
     */
    omit?: UserPermissionOmit<ExtArgs> | null
    /**
     * The data used to update UserPermissions.
     */
    data: XOR<UserPermissionUpdateManyMutationInput, UserPermissionUncheckedUpdateManyInput>
    /**
     * Filter which UserPermissions to update
     */
    where?: UserPermissionWhereInput
    /**
     * Limit how many UserPermissions to update.
     */
    limit?: number
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: UserPermissionIncludeUpdateManyAndReturn<ExtArgs> | null
  }

  /**
   * UserPermission upsert
   */
  export type UserPermissionUpsertArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the UserPermission
     */
    select?: UserPermissionSelect<ExtArgs> | null
    /**
     * Omit specific fields from the UserPermission
     */
    omit?: UserPermissionOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: UserPermissionInclude<ExtArgs> | null
    /**
     * The filter to search for the UserPermission to update in case it exists.
     */
    where: UserPermissionWhereUniqueInput
    /**
     * In case the UserPermission found by the `where` argument doesn't exist, create a new UserPermission with this data.
     */
    create: XOR<UserPermissionCreateInput, UserPermissionUncheckedCreateInput>
    /**
     * In case the UserPermission was found with the provided `where` argument, update it with this data.
     */
    update: XOR<UserPermissionUpdateInput, UserPermissionUncheckedUpdateInput>
  }

  /**
   * UserPermission delete
   */
  export type UserPermissionDeleteArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the UserPermission
     */
    select?: UserPermissionSelect<ExtArgs> | null
    /**
     * Omit specific fields from the UserPermission
     */
    omit?: UserPermissionOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: UserPermissionInclude<ExtArgs> | null
    /**
     * Filter which UserPermission to delete.
     */
    where: UserPermissionWhereUniqueInput
  }

  /**
   * UserPermission deleteMany
   */
  export type UserPermissionDeleteManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Filter which UserPermissions to delete
     */
    where?: UserPermissionWhereInput
    /**
     * Limit how many UserPermissions to delete.
     */
    limit?: number
  }

  /**
   * UserPermission without action
   */
  export type UserPermissionDefaultArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the UserPermission
     */
    select?: UserPermissionSelect<ExtArgs> | null
    /**
     * Omit specific fields from the UserPermission
     */
    omit?: UserPermissionOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: UserPermissionInclude<ExtArgs> | null
  }


  /**
   * Model UserRole
   */

  export type AggregateUserRole = {
    _count: UserRoleCountAggregateOutputType | null
    _min: UserRoleMinAggregateOutputType | null
    _max: UserRoleMaxAggregateOutputType | null
  }

  export type UserRoleMinAggregateOutputType = {
    id: string | null
    userId: string | null
    roleId: string | null
    createdAt: Date | null
  }

  export type UserRoleMaxAggregateOutputType = {
    id: string | null
    userId: string | null
    roleId: string | null
    createdAt: Date | null
  }

  export type UserRoleCountAggregateOutputType = {
    id: number
    userId: number
    roleId: number
    createdAt: number
    _all: number
  }


  export type UserRoleMinAggregateInputType = {
    id?: true
    userId?: true
    roleId?: true
    createdAt?: true
  }

  export type UserRoleMaxAggregateInputType = {
    id?: true
    userId?: true
    roleId?: true
    createdAt?: true
  }

  export type UserRoleCountAggregateInputType = {
    id?: true
    userId?: true
    roleId?: true
    createdAt?: true
    _all?: true
  }

  export type UserRoleAggregateArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Filter which UserRole to aggregate.
     */
    where?: UserRoleWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of UserRoles to fetch.
     */
    orderBy?: UserRoleOrderByWithRelationInput | UserRoleOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the start position
     */
    cursor?: UserRoleWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` UserRoles from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` UserRoles.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Count returned UserRoles
    **/
    _count?: true | UserRoleCountAggregateInputType
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Select which fields to find the minimum value
    **/
    _min?: UserRoleMinAggregateInputType
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Select which fields to find the maximum value
    **/
    _max?: UserRoleMaxAggregateInputType
  }

  export type GetUserRoleAggregateType<T extends UserRoleAggregateArgs> = {
        [P in keyof T & keyof AggregateUserRole]: P extends '_count' | 'count'
      ? T[P] extends true
        ? number
        : GetScalarType<T[P], AggregateUserRole[P]>
      : GetScalarType<T[P], AggregateUserRole[P]>
  }




  export type UserRoleGroupByArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    where?: UserRoleWhereInput
    orderBy?: UserRoleOrderByWithAggregationInput | UserRoleOrderByWithAggregationInput[]
    by: UserRoleScalarFieldEnum[] | UserRoleScalarFieldEnum
    having?: UserRoleScalarWhereWithAggregatesInput
    take?: number
    skip?: number
    _count?: UserRoleCountAggregateInputType | true
    _min?: UserRoleMinAggregateInputType
    _max?: UserRoleMaxAggregateInputType
  }

  export type UserRoleGroupByOutputType = {
    id: string
    userId: string
    roleId: string
    createdAt: Date
    _count: UserRoleCountAggregateOutputType | null
    _min: UserRoleMinAggregateOutputType | null
    _max: UserRoleMaxAggregateOutputType | null
  }

  type GetUserRoleGroupByPayload<T extends UserRoleGroupByArgs> = Prisma.PrismaPromise<
    Array<
      PickEnumerable<UserRoleGroupByOutputType, T['by']> &
        {
          [P in ((keyof T) & (keyof UserRoleGroupByOutputType))]: P extends '_count'
            ? T[P] extends boolean
              ? number
              : GetScalarType<T[P], UserRoleGroupByOutputType[P]>
            : GetScalarType<T[P], UserRoleGroupByOutputType[P]>
        }
      >
    >


  export type UserRoleSelect<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetSelect<{
    id?: boolean
    userId?: boolean
    roleId?: boolean
    createdAt?: boolean
    user?: boolean | UserDefaultArgs<ExtArgs>
    role?: boolean | RoleDefaultArgs<ExtArgs>
  }, ExtArgs["result"]["userRole"]>

  export type UserRoleSelectCreateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetSelect<{
    id?: boolean
    userId?: boolean
    roleId?: boolean
    createdAt?: boolean
    user?: boolean | UserDefaultArgs<ExtArgs>
    role?: boolean | RoleDefaultArgs<ExtArgs>
  }, ExtArgs["result"]["userRole"]>

  export type UserRoleSelectUpdateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetSelect<{
    id?: boolean
    userId?: boolean
    roleId?: boolean
    createdAt?: boolean
    user?: boolean | UserDefaultArgs<ExtArgs>
    role?: boolean | RoleDefaultArgs<ExtArgs>
  }, ExtArgs["result"]["userRole"]>

  export type UserRoleSelectScalar = {
    id?: boolean
    userId?: boolean
    roleId?: boolean
    createdAt?: boolean
  }

  export type UserRoleOmit<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetOmit<"id" | "userId" | "roleId" | "createdAt", ExtArgs["result"]["userRole"]>
  export type UserRoleInclude<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    user?: boolean | UserDefaultArgs<ExtArgs>
    role?: boolean | RoleDefaultArgs<ExtArgs>
  }
  export type UserRoleIncludeCreateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    user?: boolean | UserDefaultArgs<ExtArgs>
    role?: boolean | RoleDefaultArgs<ExtArgs>
  }
  export type UserRoleIncludeUpdateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    user?: boolean | UserDefaultArgs<ExtArgs>
    role?: boolean | RoleDefaultArgs<ExtArgs>
  }

  export type $UserRolePayload<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    name: "UserRole"
    objects: {
      user: Prisma.$UserPayload<ExtArgs>
      role: Prisma.$RolePayload<ExtArgs>
    }
    scalars: $Extensions.GetPayloadResult<{
      id: string
      userId: string
      roleId: string
      createdAt: Date
    }, ExtArgs["result"]["userRole"]>
    composites: {}
  }

  type UserRoleGetPayload<S extends boolean | null | undefined | UserRoleDefaultArgs> = $Result.GetResult<Prisma.$UserRolePayload, S>

  type UserRoleCountArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> =
    Omit<UserRoleFindManyArgs, 'select' | 'include' | 'distinct' | 'omit'> & {
      select?: UserRoleCountAggregateInputType | true
    }

  export interface UserRoleDelegate<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs, GlobalOmitOptions = {}> {
    [K: symbol]: { types: Prisma.TypeMap<ExtArgs>['model']['UserRole'], meta: { name: 'UserRole' } }
    /**
     * Find zero or one UserRole that matches the filter.
     * @param {UserRoleFindUniqueArgs} args - Arguments to find a UserRole
     * @example
     * // Get one UserRole
     * const userRole = await prisma.userRole.findUnique({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findUnique<T extends UserRoleFindUniqueArgs>(args: SelectSubset<T, UserRoleFindUniqueArgs<ExtArgs>>): Prisma__UserRoleClient<$Result.GetResult<Prisma.$UserRolePayload<ExtArgs>, T, "findUnique", GlobalOmitOptions> | null, null, ExtArgs, GlobalOmitOptions>

    /**
     * Find one UserRole that matches the filter or throw an error with `error.code='P2025'`
     * if no matches were found.
     * @param {UserRoleFindUniqueOrThrowArgs} args - Arguments to find a UserRole
     * @example
     * // Get one UserRole
     * const userRole = await prisma.userRole.findUniqueOrThrow({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findUniqueOrThrow<T extends UserRoleFindUniqueOrThrowArgs>(args: SelectSubset<T, UserRoleFindUniqueOrThrowArgs<ExtArgs>>): Prisma__UserRoleClient<$Result.GetResult<Prisma.$UserRolePayload<ExtArgs>, T, "findUniqueOrThrow", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Find the first UserRole that matches the filter.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {UserRoleFindFirstArgs} args - Arguments to find a UserRole
     * @example
     * // Get one UserRole
     * const userRole = await prisma.userRole.findFirst({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findFirst<T extends UserRoleFindFirstArgs>(args?: SelectSubset<T, UserRoleFindFirstArgs<ExtArgs>>): Prisma__UserRoleClient<$Result.GetResult<Prisma.$UserRolePayload<ExtArgs>, T, "findFirst", GlobalOmitOptions> | null, null, ExtArgs, GlobalOmitOptions>

    /**
     * Find the first UserRole that matches the filter or
     * throw `PrismaKnownClientError` with `P2025` code if no matches were found.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {UserRoleFindFirstOrThrowArgs} args - Arguments to find a UserRole
     * @example
     * // Get one UserRole
     * const userRole = await prisma.userRole.findFirstOrThrow({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findFirstOrThrow<T extends UserRoleFindFirstOrThrowArgs>(args?: SelectSubset<T, UserRoleFindFirstOrThrowArgs<ExtArgs>>): Prisma__UserRoleClient<$Result.GetResult<Prisma.$UserRolePayload<ExtArgs>, T, "findFirstOrThrow", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Find zero or more UserRoles that matches the filter.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {UserRoleFindManyArgs} args - Arguments to filter and select certain fields only.
     * @example
     * // Get all UserRoles
     * const userRoles = await prisma.userRole.findMany()
     * 
     * // Get first 10 UserRoles
     * const userRoles = await prisma.userRole.findMany({ take: 10 })
     * 
     * // Only select the `id`
     * const userRoleWithIdOnly = await prisma.userRole.findMany({ select: { id: true } })
     * 
     */
    findMany<T extends UserRoleFindManyArgs>(args?: SelectSubset<T, UserRoleFindManyArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$UserRolePayload<ExtArgs>, T, "findMany", GlobalOmitOptions>>

    /**
     * Create a UserRole.
     * @param {UserRoleCreateArgs} args - Arguments to create a UserRole.
     * @example
     * // Create one UserRole
     * const UserRole = await prisma.userRole.create({
     *   data: {
     *     // ... data to create a UserRole
     *   }
     * })
     * 
     */
    create<T extends UserRoleCreateArgs>(args: SelectSubset<T, UserRoleCreateArgs<ExtArgs>>): Prisma__UserRoleClient<$Result.GetResult<Prisma.$UserRolePayload<ExtArgs>, T, "create", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Create many UserRoles.
     * @param {UserRoleCreateManyArgs} args - Arguments to create many UserRoles.
     * @example
     * // Create many UserRoles
     * const userRole = await prisma.userRole.createMany({
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     *     
     */
    createMany<T extends UserRoleCreateManyArgs>(args?: SelectSubset<T, UserRoleCreateManyArgs<ExtArgs>>): Prisma.PrismaPromise<BatchPayload>

    /**
     * Create many UserRoles and returns the data saved in the database.
     * @param {UserRoleCreateManyAndReturnArgs} args - Arguments to create many UserRoles.
     * @example
     * // Create many UserRoles
     * const userRole = await prisma.userRole.createManyAndReturn({
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * 
     * // Create many UserRoles and only return the `id`
     * const userRoleWithIdOnly = await prisma.userRole.createManyAndReturn({
     *   select: { id: true },
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * 
     */
    createManyAndReturn<T extends UserRoleCreateManyAndReturnArgs>(args?: SelectSubset<T, UserRoleCreateManyAndReturnArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$UserRolePayload<ExtArgs>, T, "createManyAndReturn", GlobalOmitOptions>>

    /**
     * Delete a UserRole.
     * @param {UserRoleDeleteArgs} args - Arguments to delete one UserRole.
     * @example
     * // Delete one UserRole
     * const UserRole = await prisma.userRole.delete({
     *   where: {
     *     // ... filter to delete one UserRole
     *   }
     * })
     * 
     */
    delete<T extends UserRoleDeleteArgs>(args: SelectSubset<T, UserRoleDeleteArgs<ExtArgs>>): Prisma__UserRoleClient<$Result.GetResult<Prisma.$UserRolePayload<ExtArgs>, T, "delete", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Update one UserRole.
     * @param {UserRoleUpdateArgs} args - Arguments to update one UserRole.
     * @example
     * // Update one UserRole
     * const userRole = await prisma.userRole.update({
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: {
     *     // ... provide data here
     *   }
     * })
     * 
     */
    update<T extends UserRoleUpdateArgs>(args: SelectSubset<T, UserRoleUpdateArgs<ExtArgs>>): Prisma__UserRoleClient<$Result.GetResult<Prisma.$UserRolePayload<ExtArgs>, T, "update", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Delete zero or more UserRoles.
     * @param {UserRoleDeleteManyArgs} args - Arguments to filter UserRoles to delete.
     * @example
     * // Delete a few UserRoles
     * const { count } = await prisma.userRole.deleteMany({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     * 
     */
    deleteMany<T extends UserRoleDeleteManyArgs>(args?: SelectSubset<T, UserRoleDeleteManyArgs<ExtArgs>>): Prisma.PrismaPromise<BatchPayload>

    /**
     * Update zero or more UserRoles.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {UserRoleUpdateManyArgs} args - Arguments to update one or more rows.
     * @example
     * // Update many UserRoles
     * const userRole = await prisma.userRole.updateMany({
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: {
     *     // ... provide data here
     *   }
     * })
     * 
     */
    updateMany<T extends UserRoleUpdateManyArgs>(args: SelectSubset<T, UserRoleUpdateManyArgs<ExtArgs>>): Prisma.PrismaPromise<BatchPayload>

    /**
     * Update zero or more UserRoles and returns the data updated in the database.
     * @param {UserRoleUpdateManyAndReturnArgs} args - Arguments to update many UserRoles.
     * @example
     * // Update many UserRoles
     * const userRole = await prisma.userRole.updateManyAndReturn({
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * 
     * // Update zero or more UserRoles and only return the `id`
     * const userRoleWithIdOnly = await prisma.userRole.updateManyAndReturn({
     *   select: { id: true },
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * 
     */
    updateManyAndReturn<T extends UserRoleUpdateManyAndReturnArgs>(args: SelectSubset<T, UserRoleUpdateManyAndReturnArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$UserRolePayload<ExtArgs>, T, "updateManyAndReturn", GlobalOmitOptions>>

    /**
     * Create or update one UserRole.
     * @param {UserRoleUpsertArgs} args - Arguments to update or create a UserRole.
     * @example
     * // Update or create a UserRole
     * const userRole = await prisma.userRole.upsert({
     *   create: {
     *     // ... data to create a UserRole
     *   },
     *   update: {
     *     // ... in case it already exists, update
     *   },
     *   where: {
     *     // ... the filter for the UserRole we want to update
     *   }
     * })
     */
    upsert<T extends UserRoleUpsertArgs>(args: SelectSubset<T, UserRoleUpsertArgs<ExtArgs>>): Prisma__UserRoleClient<$Result.GetResult<Prisma.$UserRolePayload<ExtArgs>, T, "upsert", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>


    /**
     * Count the number of UserRoles.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {UserRoleCountArgs} args - Arguments to filter UserRoles to count.
     * @example
     * // Count the number of UserRoles
     * const count = await prisma.userRole.count({
     *   where: {
     *     // ... the filter for the UserRoles we want to count
     *   }
     * })
    **/
    count<T extends UserRoleCountArgs>(
      args?: Subset<T, UserRoleCountArgs>,
    ): Prisma.PrismaPromise<
      T extends $Utils.Record<'select', any>
        ? T['select'] extends true
          ? number
          : GetScalarType<T['select'], UserRoleCountAggregateOutputType>
        : number
    >

    /**
     * Allows you to perform aggregations operations on a UserRole.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {UserRoleAggregateArgs} args - Select which aggregations you would like to apply and on what fields.
     * @example
     * // Ordered by age ascending
     * // Where email contains prisma.io
     * // Limited to the 10 users
     * const aggregations = await prisma.user.aggregate({
     *   _avg: {
     *     age: true,
     *   },
     *   where: {
     *     email: {
     *       contains: "prisma.io",
     *     },
     *   },
     *   orderBy: {
     *     age: "asc",
     *   },
     *   take: 10,
     * })
    **/
    aggregate<T extends UserRoleAggregateArgs>(args: Subset<T, UserRoleAggregateArgs>): Prisma.PrismaPromise<GetUserRoleAggregateType<T>>

    /**
     * Group by UserRole.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {UserRoleGroupByArgs} args - Group by arguments.
     * @example
     * // Group by city, order by createdAt, get count
     * const result = await prisma.user.groupBy({
     *   by: ['city', 'createdAt'],
     *   orderBy: {
     *     createdAt: true
     *   },
     *   _count: {
     *     _all: true
     *   },
     * })
     * 
    **/
    groupBy<
      T extends UserRoleGroupByArgs,
      HasSelectOrTake extends Or<
        Extends<'skip', Keys<T>>,
        Extends<'take', Keys<T>>
      >,
      OrderByArg extends True extends HasSelectOrTake
        ? { orderBy: UserRoleGroupByArgs['orderBy'] }
        : { orderBy?: UserRoleGroupByArgs['orderBy'] },
      OrderFields extends ExcludeUnderscoreKeys<Keys<MaybeTupleToUnion<T['orderBy']>>>,
      ByFields extends MaybeTupleToUnion<T['by']>,
      ByValid extends Has<ByFields, OrderFields>,
      HavingFields extends GetHavingFields<T['having']>,
      HavingValid extends Has<ByFields, HavingFields>,
      ByEmpty extends T['by'] extends never[] ? True : False,
      InputErrors extends ByEmpty extends True
      ? `Error: "by" must not be empty.`
      : HavingValid extends False
      ? {
          [P in HavingFields]: P extends ByFields
            ? never
            : P extends string
            ? `Error: Field "${P}" used in "having" needs to be provided in "by".`
            : [
                Error,
                'Field ',
                P,
                ` in "having" needs to be provided in "by"`,
              ]
        }[HavingFields]
      : 'take' extends Keys<T>
      ? 'orderBy' extends Keys<T>
        ? ByValid extends True
          ? {}
          : {
              [P in OrderFields]: P extends ByFields
                ? never
                : `Error: Field "${P}" in "orderBy" needs to be provided in "by"`
            }[OrderFields]
        : 'Error: If you provide "take", you also need to provide "orderBy"'
      : 'skip' extends Keys<T>
      ? 'orderBy' extends Keys<T>
        ? ByValid extends True
          ? {}
          : {
              [P in OrderFields]: P extends ByFields
                ? never
                : `Error: Field "${P}" in "orderBy" needs to be provided in "by"`
            }[OrderFields]
        : 'Error: If you provide "skip", you also need to provide "orderBy"'
      : ByValid extends True
      ? {}
      : {
          [P in OrderFields]: P extends ByFields
            ? never
            : `Error: Field "${P}" in "orderBy" needs to be provided in "by"`
        }[OrderFields]
    >(args: SubsetIntersection<T, UserRoleGroupByArgs, OrderByArg> & InputErrors): {} extends InputErrors ? GetUserRoleGroupByPayload<T> : Prisma.PrismaPromise<InputErrors>
  /**
   * Fields of the UserRole model
   */
  readonly fields: UserRoleFieldRefs;
  }

  /**
   * The delegate class that acts as a "Promise-like" for UserRole.
   * Why is this prefixed with `Prisma__`?
   * Because we want to prevent naming conflicts as mentioned in
   * https://github.com/prisma/prisma-client-js/issues/707
   */
  export interface Prisma__UserRoleClient<T, Null = never, ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs, GlobalOmitOptions = {}> extends Prisma.PrismaPromise<T> {
    readonly [Symbol.toStringTag]: "PrismaPromise"
    user<T extends UserDefaultArgs<ExtArgs> = {}>(args?: Subset<T, UserDefaultArgs<ExtArgs>>): Prisma__UserClient<$Result.GetResult<Prisma.$UserPayload<ExtArgs>, T, "findUniqueOrThrow", GlobalOmitOptions> | Null, Null, ExtArgs, GlobalOmitOptions>
    role<T extends RoleDefaultArgs<ExtArgs> = {}>(args?: Subset<T, RoleDefaultArgs<ExtArgs>>): Prisma__RoleClient<$Result.GetResult<Prisma.$RolePayload<ExtArgs>, T, "findUniqueOrThrow", GlobalOmitOptions> | Null, Null, ExtArgs, GlobalOmitOptions>
    /**
     * Attaches callbacks for the resolution and/or rejection of the Promise.
     * @param onfulfilled The callback to execute when the Promise is resolved.
     * @param onrejected The callback to execute when the Promise is rejected.
     * @returns A Promise for the completion of which ever callback is executed.
     */
    then<TResult1 = T, TResult2 = never>(onfulfilled?: ((value: T) => TResult1 | PromiseLike<TResult1>) | undefined | null, onrejected?: ((reason: any) => TResult2 | PromiseLike<TResult2>) | undefined | null): $Utils.JsPromise<TResult1 | TResult2>
    /**
     * Attaches a callback for only the rejection of the Promise.
     * @param onrejected The callback to execute when the Promise is rejected.
     * @returns A Promise for the completion of the callback.
     */
    catch<TResult = never>(onrejected?: ((reason: any) => TResult | PromiseLike<TResult>) | undefined | null): $Utils.JsPromise<T | TResult>
    /**
     * Attaches a callback that is invoked when the Promise is settled (fulfilled or rejected). The
     * resolved value cannot be modified from the callback.
     * @param onfinally The callback to execute when the Promise is settled (fulfilled or rejected).
     * @returns A Promise for the completion of the callback.
     */
    finally(onfinally?: (() => void) | undefined | null): $Utils.JsPromise<T>
  }




  /**
   * Fields of the UserRole model
   */
  interface UserRoleFieldRefs {
    readonly id: FieldRef<"UserRole", 'String'>
    readonly userId: FieldRef<"UserRole", 'String'>
    readonly roleId: FieldRef<"UserRole", 'String'>
    readonly createdAt: FieldRef<"UserRole", 'DateTime'>
  }
    

  // Custom InputTypes
  /**
   * UserRole findUnique
   */
  export type UserRoleFindUniqueArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the UserRole
     */
    select?: UserRoleSelect<ExtArgs> | null
    /**
     * Omit specific fields from the UserRole
     */
    omit?: UserRoleOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: UserRoleInclude<ExtArgs> | null
    /**
     * Filter, which UserRole to fetch.
     */
    where: UserRoleWhereUniqueInput
  }

  /**
   * UserRole findUniqueOrThrow
   */
  export type UserRoleFindUniqueOrThrowArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the UserRole
     */
    select?: UserRoleSelect<ExtArgs> | null
    /**
     * Omit specific fields from the UserRole
     */
    omit?: UserRoleOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: UserRoleInclude<ExtArgs> | null
    /**
     * Filter, which UserRole to fetch.
     */
    where: UserRoleWhereUniqueInput
  }

  /**
   * UserRole findFirst
   */
  export type UserRoleFindFirstArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the UserRole
     */
    select?: UserRoleSelect<ExtArgs> | null
    /**
     * Omit specific fields from the UserRole
     */
    omit?: UserRoleOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: UserRoleInclude<ExtArgs> | null
    /**
     * Filter, which UserRole to fetch.
     */
    where?: UserRoleWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of UserRoles to fetch.
     */
    orderBy?: UserRoleOrderByWithRelationInput | UserRoleOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the position for searching for UserRoles.
     */
    cursor?: UserRoleWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` UserRoles from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` UserRoles.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/distinct Distinct Docs}
     * 
     * Filter by unique combinations of UserRoles.
     */
    distinct?: UserRoleScalarFieldEnum | UserRoleScalarFieldEnum[]
  }

  /**
   * UserRole findFirstOrThrow
   */
  export type UserRoleFindFirstOrThrowArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the UserRole
     */
    select?: UserRoleSelect<ExtArgs> | null
    /**
     * Omit specific fields from the UserRole
     */
    omit?: UserRoleOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: UserRoleInclude<ExtArgs> | null
    /**
     * Filter, which UserRole to fetch.
     */
    where?: UserRoleWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of UserRoles to fetch.
     */
    orderBy?: UserRoleOrderByWithRelationInput | UserRoleOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the position for searching for UserRoles.
     */
    cursor?: UserRoleWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` UserRoles from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` UserRoles.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/distinct Distinct Docs}
     * 
     * Filter by unique combinations of UserRoles.
     */
    distinct?: UserRoleScalarFieldEnum | UserRoleScalarFieldEnum[]
  }

  /**
   * UserRole findMany
   */
  export type UserRoleFindManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the UserRole
     */
    select?: UserRoleSelect<ExtArgs> | null
    /**
     * Omit specific fields from the UserRole
     */
    omit?: UserRoleOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: UserRoleInclude<ExtArgs> | null
    /**
     * Filter, which UserRoles to fetch.
     */
    where?: UserRoleWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of UserRoles to fetch.
     */
    orderBy?: UserRoleOrderByWithRelationInput | UserRoleOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the position for listing UserRoles.
     */
    cursor?: UserRoleWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` UserRoles from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` UserRoles.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/distinct Distinct Docs}
     * 
     * Filter by unique combinations of UserRoles.
     */
    distinct?: UserRoleScalarFieldEnum | UserRoleScalarFieldEnum[]
  }

  /**
   * UserRole create
   */
  export type UserRoleCreateArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the UserRole
     */
    select?: UserRoleSelect<ExtArgs> | null
    /**
     * Omit specific fields from the UserRole
     */
    omit?: UserRoleOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: UserRoleInclude<ExtArgs> | null
    /**
     * The data needed to create a UserRole.
     */
    data: XOR<UserRoleCreateInput, UserRoleUncheckedCreateInput>
  }

  /**
   * UserRole createMany
   */
  export type UserRoleCreateManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * The data used to create many UserRoles.
     */
    data: UserRoleCreateManyInput | UserRoleCreateManyInput[]
    skipDuplicates?: boolean
  }

  /**
   * UserRole createManyAndReturn
   */
  export type UserRoleCreateManyAndReturnArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the UserRole
     */
    select?: UserRoleSelectCreateManyAndReturn<ExtArgs> | null
    /**
     * Omit specific fields from the UserRole
     */
    omit?: UserRoleOmit<ExtArgs> | null
    /**
     * The data used to create many UserRoles.
     */
    data: UserRoleCreateManyInput | UserRoleCreateManyInput[]
    skipDuplicates?: boolean
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: UserRoleIncludeCreateManyAndReturn<ExtArgs> | null
  }

  /**
   * UserRole update
   */
  export type UserRoleUpdateArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the UserRole
     */
    select?: UserRoleSelect<ExtArgs> | null
    /**
     * Omit specific fields from the UserRole
     */
    omit?: UserRoleOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: UserRoleInclude<ExtArgs> | null
    /**
     * The data needed to update a UserRole.
     */
    data: XOR<UserRoleUpdateInput, UserRoleUncheckedUpdateInput>
    /**
     * Choose, which UserRole to update.
     */
    where: UserRoleWhereUniqueInput
  }

  /**
   * UserRole updateMany
   */
  export type UserRoleUpdateManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * The data used to update UserRoles.
     */
    data: XOR<UserRoleUpdateManyMutationInput, UserRoleUncheckedUpdateManyInput>
    /**
     * Filter which UserRoles to update
     */
    where?: UserRoleWhereInput
    /**
     * Limit how many UserRoles to update.
     */
    limit?: number
  }

  /**
   * UserRole updateManyAndReturn
   */
  export type UserRoleUpdateManyAndReturnArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the UserRole
     */
    select?: UserRoleSelectUpdateManyAndReturn<ExtArgs> | null
    /**
     * Omit specific fields from the UserRole
     */
    omit?: UserRoleOmit<ExtArgs> | null
    /**
     * The data used to update UserRoles.
     */
    data: XOR<UserRoleUpdateManyMutationInput, UserRoleUncheckedUpdateManyInput>
    /**
     * Filter which UserRoles to update
     */
    where?: UserRoleWhereInput
    /**
     * Limit how many UserRoles to update.
     */
    limit?: number
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: UserRoleIncludeUpdateManyAndReturn<ExtArgs> | null
  }

  /**
   * UserRole upsert
   */
  export type UserRoleUpsertArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the UserRole
     */
    select?: UserRoleSelect<ExtArgs> | null
    /**
     * Omit specific fields from the UserRole
     */
    omit?: UserRoleOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: UserRoleInclude<ExtArgs> | null
    /**
     * The filter to search for the UserRole to update in case it exists.
     */
    where: UserRoleWhereUniqueInput
    /**
     * In case the UserRole found by the `where` argument doesn't exist, create a new UserRole with this data.
     */
    create: XOR<UserRoleCreateInput, UserRoleUncheckedCreateInput>
    /**
     * In case the UserRole was found with the provided `where` argument, update it with this data.
     */
    update: XOR<UserRoleUpdateInput, UserRoleUncheckedUpdateInput>
  }

  /**
   * UserRole delete
   */
  export type UserRoleDeleteArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the UserRole
     */
    select?: UserRoleSelect<ExtArgs> | null
    /**
     * Omit specific fields from the UserRole
     */
    omit?: UserRoleOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: UserRoleInclude<ExtArgs> | null
    /**
     * Filter which UserRole to delete.
     */
    where: UserRoleWhereUniqueInput
  }

  /**
   * UserRole deleteMany
   */
  export type UserRoleDeleteManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Filter which UserRoles to delete
     */
    where?: UserRoleWhereInput
    /**
     * Limit how many UserRoles to delete.
     */
    limit?: number
  }

  /**
   * UserRole without action
   */
  export type UserRoleDefaultArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the UserRole
     */
    select?: UserRoleSelect<ExtArgs> | null
    /**
     * Omit specific fields from the UserRole
     */
    omit?: UserRoleOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: UserRoleInclude<ExtArgs> | null
  }


  /**
   * Model Task
   */

  export type AggregateTask = {
    _count: TaskCountAggregateOutputType | null
    _min: TaskMinAggregateOutputType | null
    _max: TaskMaxAggregateOutputType | null
  }

  export type TaskMinAggregateOutputType = {
    id: string | null
    title: string | null
    iconUrl: string | null
    description: string | null
    createdAt: Date | null
    updatedAt: Date | null
  }

  export type TaskMaxAggregateOutputType = {
    id: string | null
    title: string | null
    iconUrl: string | null
    description: string | null
    createdAt: Date | null
    updatedAt: Date | null
  }

  export type TaskCountAggregateOutputType = {
    id: number
    title: number
    iconUrl: number
    description: number
    createdAt: number
    updatedAt: number
    _all: number
  }


  export type TaskMinAggregateInputType = {
    id?: true
    title?: true
    iconUrl?: true
    description?: true
    createdAt?: true
    updatedAt?: true
  }

  export type TaskMaxAggregateInputType = {
    id?: true
    title?: true
    iconUrl?: true
    description?: true
    createdAt?: true
    updatedAt?: true
  }

  export type TaskCountAggregateInputType = {
    id?: true
    title?: true
    iconUrl?: true
    description?: true
    createdAt?: true
    updatedAt?: true
    _all?: true
  }

  export type TaskAggregateArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Filter which Task to aggregate.
     */
    where?: TaskWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of Tasks to fetch.
     */
    orderBy?: TaskOrderByWithRelationInput | TaskOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the start position
     */
    cursor?: TaskWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` Tasks from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` Tasks.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Count returned Tasks
    **/
    _count?: true | TaskCountAggregateInputType
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Select which fields to find the minimum value
    **/
    _min?: TaskMinAggregateInputType
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Select which fields to find the maximum value
    **/
    _max?: TaskMaxAggregateInputType
  }

  export type GetTaskAggregateType<T extends TaskAggregateArgs> = {
        [P in keyof T & keyof AggregateTask]: P extends '_count' | 'count'
      ? T[P] extends true
        ? number
        : GetScalarType<T[P], AggregateTask[P]>
      : GetScalarType<T[P], AggregateTask[P]>
  }




  export type TaskGroupByArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    where?: TaskWhereInput
    orderBy?: TaskOrderByWithAggregationInput | TaskOrderByWithAggregationInput[]
    by: TaskScalarFieldEnum[] | TaskScalarFieldEnum
    having?: TaskScalarWhereWithAggregatesInput
    take?: number
    skip?: number
    _count?: TaskCountAggregateInputType | true
    _min?: TaskMinAggregateInputType
    _max?: TaskMaxAggregateInputType
  }

  export type TaskGroupByOutputType = {
    id: string
    title: string
    iconUrl: string | null
    description: string | null
    createdAt: Date
    updatedAt: Date
    _count: TaskCountAggregateOutputType | null
    _min: TaskMinAggregateOutputType | null
    _max: TaskMaxAggregateOutputType | null
  }

  type GetTaskGroupByPayload<T extends TaskGroupByArgs> = Prisma.PrismaPromise<
    Array<
      PickEnumerable<TaskGroupByOutputType, T['by']> &
        {
          [P in ((keyof T) & (keyof TaskGroupByOutputType))]: P extends '_count'
            ? T[P] extends boolean
              ? number
              : GetScalarType<T[P], TaskGroupByOutputType[P]>
            : GetScalarType<T[P], TaskGroupByOutputType[P]>
        }
      >
    >


  export type TaskSelect<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetSelect<{
    id?: boolean
    title?: boolean
    iconUrl?: boolean
    description?: boolean
    createdAt?: boolean
    updatedAt?: boolean
    tiles?: boolean | Task$tilesArgs<ExtArgs>
    _count?: boolean | TaskCountOutputTypeDefaultArgs<ExtArgs>
  }, ExtArgs["result"]["task"]>

  export type TaskSelectCreateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetSelect<{
    id?: boolean
    title?: boolean
    iconUrl?: boolean
    description?: boolean
    createdAt?: boolean
    updatedAt?: boolean
  }, ExtArgs["result"]["task"]>

  export type TaskSelectUpdateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetSelect<{
    id?: boolean
    title?: boolean
    iconUrl?: boolean
    description?: boolean
    createdAt?: boolean
    updatedAt?: boolean
  }, ExtArgs["result"]["task"]>

  export type TaskSelectScalar = {
    id?: boolean
    title?: boolean
    iconUrl?: boolean
    description?: boolean
    createdAt?: boolean
    updatedAt?: boolean
  }

  export type TaskOmit<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetOmit<"id" | "title" | "iconUrl" | "description" | "createdAt" | "updatedAt", ExtArgs["result"]["task"]>
  export type TaskInclude<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    tiles?: boolean | Task$tilesArgs<ExtArgs>
    _count?: boolean | TaskCountOutputTypeDefaultArgs<ExtArgs>
  }
  export type TaskIncludeCreateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {}
  export type TaskIncludeUpdateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {}

  export type $TaskPayload<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    name: "Task"
    objects: {
      tiles: Prisma.$TilePayload<ExtArgs>[]
    }
    scalars: $Extensions.GetPayloadResult<{
      id: string
      title: string
      iconUrl: string | null
      description: string | null
      createdAt: Date
      updatedAt: Date
    }, ExtArgs["result"]["task"]>
    composites: {}
  }

  type TaskGetPayload<S extends boolean | null | undefined | TaskDefaultArgs> = $Result.GetResult<Prisma.$TaskPayload, S>

  type TaskCountArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> =
    Omit<TaskFindManyArgs, 'select' | 'include' | 'distinct' | 'omit'> & {
      select?: TaskCountAggregateInputType | true
    }

  export interface TaskDelegate<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs, GlobalOmitOptions = {}> {
    [K: symbol]: { types: Prisma.TypeMap<ExtArgs>['model']['Task'], meta: { name: 'Task' } }
    /**
     * Find zero or one Task that matches the filter.
     * @param {TaskFindUniqueArgs} args - Arguments to find a Task
     * @example
     * // Get one Task
     * const task = await prisma.task.findUnique({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findUnique<T extends TaskFindUniqueArgs>(args: SelectSubset<T, TaskFindUniqueArgs<ExtArgs>>): Prisma__TaskClient<$Result.GetResult<Prisma.$TaskPayload<ExtArgs>, T, "findUnique", GlobalOmitOptions> | null, null, ExtArgs, GlobalOmitOptions>

    /**
     * Find one Task that matches the filter or throw an error with `error.code='P2025'`
     * if no matches were found.
     * @param {TaskFindUniqueOrThrowArgs} args - Arguments to find a Task
     * @example
     * // Get one Task
     * const task = await prisma.task.findUniqueOrThrow({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findUniqueOrThrow<T extends TaskFindUniqueOrThrowArgs>(args: SelectSubset<T, TaskFindUniqueOrThrowArgs<ExtArgs>>): Prisma__TaskClient<$Result.GetResult<Prisma.$TaskPayload<ExtArgs>, T, "findUniqueOrThrow", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Find the first Task that matches the filter.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {TaskFindFirstArgs} args - Arguments to find a Task
     * @example
     * // Get one Task
     * const task = await prisma.task.findFirst({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findFirst<T extends TaskFindFirstArgs>(args?: SelectSubset<T, TaskFindFirstArgs<ExtArgs>>): Prisma__TaskClient<$Result.GetResult<Prisma.$TaskPayload<ExtArgs>, T, "findFirst", GlobalOmitOptions> | null, null, ExtArgs, GlobalOmitOptions>

    /**
     * Find the first Task that matches the filter or
     * throw `PrismaKnownClientError` with `P2025` code if no matches were found.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {TaskFindFirstOrThrowArgs} args - Arguments to find a Task
     * @example
     * // Get one Task
     * const task = await prisma.task.findFirstOrThrow({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findFirstOrThrow<T extends TaskFindFirstOrThrowArgs>(args?: SelectSubset<T, TaskFindFirstOrThrowArgs<ExtArgs>>): Prisma__TaskClient<$Result.GetResult<Prisma.$TaskPayload<ExtArgs>, T, "findFirstOrThrow", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Find zero or more Tasks that matches the filter.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {TaskFindManyArgs} args - Arguments to filter and select certain fields only.
     * @example
     * // Get all Tasks
     * const tasks = await prisma.task.findMany()
     * 
     * // Get first 10 Tasks
     * const tasks = await prisma.task.findMany({ take: 10 })
     * 
     * // Only select the `id`
     * const taskWithIdOnly = await prisma.task.findMany({ select: { id: true } })
     * 
     */
    findMany<T extends TaskFindManyArgs>(args?: SelectSubset<T, TaskFindManyArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$TaskPayload<ExtArgs>, T, "findMany", GlobalOmitOptions>>

    /**
     * Create a Task.
     * @param {TaskCreateArgs} args - Arguments to create a Task.
     * @example
     * // Create one Task
     * const Task = await prisma.task.create({
     *   data: {
     *     // ... data to create a Task
     *   }
     * })
     * 
     */
    create<T extends TaskCreateArgs>(args: SelectSubset<T, TaskCreateArgs<ExtArgs>>): Prisma__TaskClient<$Result.GetResult<Prisma.$TaskPayload<ExtArgs>, T, "create", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Create many Tasks.
     * @param {TaskCreateManyArgs} args - Arguments to create many Tasks.
     * @example
     * // Create many Tasks
     * const task = await prisma.task.createMany({
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     *     
     */
    createMany<T extends TaskCreateManyArgs>(args?: SelectSubset<T, TaskCreateManyArgs<ExtArgs>>): Prisma.PrismaPromise<BatchPayload>

    /**
     * Create many Tasks and returns the data saved in the database.
     * @param {TaskCreateManyAndReturnArgs} args - Arguments to create many Tasks.
     * @example
     * // Create many Tasks
     * const task = await prisma.task.createManyAndReturn({
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * 
     * // Create many Tasks and only return the `id`
     * const taskWithIdOnly = await prisma.task.createManyAndReturn({
     *   select: { id: true },
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * 
     */
    createManyAndReturn<T extends TaskCreateManyAndReturnArgs>(args?: SelectSubset<T, TaskCreateManyAndReturnArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$TaskPayload<ExtArgs>, T, "createManyAndReturn", GlobalOmitOptions>>

    /**
     * Delete a Task.
     * @param {TaskDeleteArgs} args - Arguments to delete one Task.
     * @example
     * // Delete one Task
     * const Task = await prisma.task.delete({
     *   where: {
     *     // ... filter to delete one Task
     *   }
     * })
     * 
     */
    delete<T extends TaskDeleteArgs>(args: SelectSubset<T, TaskDeleteArgs<ExtArgs>>): Prisma__TaskClient<$Result.GetResult<Prisma.$TaskPayload<ExtArgs>, T, "delete", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Update one Task.
     * @param {TaskUpdateArgs} args - Arguments to update one Task.
     * @example
     * // Update one Task
     * const task = await prisma.task.update({
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: {
     *     // ... provide data here
     *   }
     * })
     * 
     */
    update<T extends TaskUpdateArgs>(args: SelectSubset<T, TaskUpdateArgs<ExtArgs>>): Prisma__TaskClient<$Result.GetResult<Prisma.$TaskPayload<ExtArgs>, T, "update", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Delete zero or more Tasks.
     * @param {TaskDeleteManyArgs} args - Arguments to filter Tasks to delete.
     * @example
     * // Delete a few Tasks
     * const { count } = await prisma.task.deleteMany({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     * 
     */
    deleteMany<T extends TaskDeleteManyArgs>(args?: SelectSubset<T, TaskDeleteManyArgs<ExtArgs>>): Prisma.PrismaPromise<BatchPayload>

    /**
     * Update zero or more Tasks.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {TaskUpdateManyArgs} args - Arguments to update one or more rows.
     * @example
     * // Update many Tasks
     * const task = await prisma.task.updateMany({
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: {
     *     // ... provide data here
     *   }
     * })
     * 
     */
    updateMany<T extends TaskUpdateManyArgs>(args: SelectSubset<T, TaskUpdateManyArgs<ExtArgs>>): Prisma.PrismaPromise<BatchPayload>

    /**
     * Update zero or more Tasks and returns the data updated in the database.
     * @param {TaskUpdateManyAndReturnArgs} args - Arguments to update many Tasks.
     * @example
     * // Update many Tasks
     * const task = await prisma.task.updateManyAndReturn({
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * 
     * // Update zero or more Tasks and only return the `id`
     * const taskWithIdOnly = await prisma.task.updateManyAndReturn({
     *   select: { id: true },
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * 
     */
    updateManyAndReturn<T extends TaskUpdateManyAndReturnArgs>(args: SelectSubset<T, TaskUpdateManyAndReturnArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$TaskPayload<ExtArgs>, T, "updateManyAndReturn", GlobalOmitOptions>>

    /**
     * Create or update one Task.
     * @param {TaskUpsertArgs} args - Arguments to update or create a Task.
     * @example
     * // Update or create a Task
     * const task = await prisma.task.upsert({
     *   create: {
     *     // ... data to create a Task
     *   },
     *   update: {
     *     // ... in case it already exists, update
     *   },
     *   where: {
     *     // ... the filter for the Task we want to update
     *   }
     * })
     */
    upsert<T extends TaskUpsertArgs>(args: SelectSubset<T, TaskUpsertArgs<ExtArgs>>): Prisma__TaskClient<$Result.GetResult<Prisma.$TaskPayload<ExtArgs>, T, "upsert", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>


    /**
     * Count the number of Tasks.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {TaskCountArgs} args - Arguments to filter Tasks to count.
     * @example
     * // Count the number of Tasks
     * const count = await prisma.task.count({
     *   where: {
     *     // ... the filter for the Tasks we want to count
     *   }
     * })
    **/
    count<T extends TaskCountArgs>(
      args?: Subset<T, TaskCountArgs>,
    ): Prisma.PrismaPromise<
      T extends $Utils.Record<'select', any>
        ? T['select'] extends true
          ? number
          : GetScalarType<T['select'], TaskCountAggregateOutputType>
        : number
    >

    /**
     * Allows you to perform aggregations operations on a Task.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {TaskAggregateArgs} args - Select which aggregations you would like to apply and on what fields.
     * @example
     * // Ordered by age ascending
     * // Where email contains prisma.io
     * // Limited to the 10 users
     * const aggregations = await prisma.user.aggregate({
     *   _avg: {
     *     age: true,
     *   },
     *   where: {
     *     email: {
     *       contains: "prisma.io",
     *     },
     *   },
     *   orderBy: {
     *     age: "asc",
     *   },
     *   take: 10,
     * })
    **/
    aggregate<T extends TaskAggregateArgs>(args: Subset<T, TaskAggregateArgs>): Prisma.PrismaPromise<GetTaskAggregateType<T>>

    /**
     * Group by Task.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {TaskGroupByArgs} args - Group by arguments.
     * @example
     * // Group by city, order by createdAt, get count
     * const result = await prisma.user.groupBy({
     *   by: ['city', 'createdAt'],
     *   orderBy: {
     *     createdAt: true
     *   },
     *   _count: {
     *     _all: true
     *   },
     * })
     * 
    **/
    groupBy<
      T extends TaskGroupByArgs,
      HasSelectOrTake extends Or<
        Extends<'skip', Keys<T>>,
        Extends<'take', Keys<T>>
      >,
      OrderByArg extends True extends HasSelectOrTake
        ? { orderBy: TaskGroupByArgs['orderBy'] }
        : { orderBy?: TaskGroupByArgs['orderBy'] },
      OrderFields extends ExcludeUnderscoreKeys<Keys<MaybeTupleToUnion<T['orderBy']>>>,
      ByFields extends MaybeTupleToUnion<T['by']>,
      ByValid extends Has<ByFields, OrderFields>,
      HavingFields extends GetHavingFields<T['having']>,
      HavingValid extends Has<ByFields, HavingFields>,
      ByEmpty extends T['by'] extends never[] ? True : False,
      InputErrors extends ByEmpty extends True
      ? `Error: "by" must not be empty.`
      : HavingValid extends False
      ? {
          [P in HavingFields]: P extends ByFields
            ? never
            : P extends string
            ? `Error: Field "${P}" used in "having" needs to be provided in "by".`
            : [
                Error,
                'Field ',
                P,
                ` in "having" needs to be provided in "by"`,
              ]
        }[HavingFields]
      : 'take' extends Keys<T>
      ? 'orderBy' extends Keys<T>
        ? ByValid extends True
          ? {}
          : {
              [P in OrderFields]: P extends ByFields
                ? never
                : `Error: Field "${P}" in "orderBy" needs to be provided in "by"`
            }[OrderFields]
        : 'Error: If you provide "take", you also need to provide "orderBy"'
      : 'skip' extends Keys<T>
      ? 'orderBy' extends Keys<T>
        ? ByValid extends True
          ? {}
          : {
              [P in OrderFields]: P extends ByFields
                ? never
                : `Error: Field "${P}" in "orderBy" needs to be provided in "by"`
            }[OrderFields]
        : 'Error: If you provide "skip", you also need to provide "orderBy"'
      : ByValid extends True
      ? {}
      : {
          [P in OrderFields]: P extends ByFields
            ? never
            : `Error: Field "${P}" in "orderBy" needs to be provided in "by"`
        }[OrderFields]
    >(args: SubsetIntersection<T, TaskGroupByArgs, OrderByArg> & InputErrors): {} extends InputErrors ? GetTaskGroupByPayload<T> : Prisma.PrismaPromise<InputErrors>
  /**
   * Fields of the Task model
   */
  readonly fields: TaskFieldRefs;
  }

  /**
   * The delegate class that acts as a "Promise-like" for Task.
   * Why is this prefixed with `Prisma__`?
   * Because we want to prevent naming conflicts as mentioned in
   * https://github.com/prisma/prisma-client-js/issues/707
   */
  export interface Prisma__TaskClient<T, Null = never, ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs, GlobalOmitOptions = {}> extends Prisma.PrismaPromise<T> {
    readonly [Symbol.toStringTag]: "PrismaPromise"
    tiles<T extends Task$tilesArgs<ExtArgs> = {}>(args?: Subset<T, Task$tilesArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$TilePayload<ExtArgs>, T, "findMany", GlobalOmitOptions> | Null>
    /**
     * Attaches callbacks for the resolution and/or rejection of the Promise.
     * @param onfulfilled The callback to execute when the Promise is resolved.
     * @param onrejected The callback to execute when the Promise is rejected.
     * @returns A Promise for the completion of which ever callback is executed.
     */
    then<TResult1 = T, TResult2 = never>(onfulfilled?: ((value: T) => TResult1 | PromiseLike<TResult1>) | undefined | null, onrejected?: ((reason: any) => TResult2 | PromiseLike<TResult2>) | undefined | null): $Utils.JsPromise<TResult1 | TResult2>
    /**
     * Attaches a callback for only the rejection of the Promise.
     * @param onrejected The callback to execute when the Promise is rejected.
     * @returns A Promise for the completion of the callback.
     */
    catch<TResult = never>(onrejected?: ((reason: any) => TResult | PromiseLike<TResult>) | undefined | null): $Utils.JsPromise<T | TResult>
    /**
     * Attaches a callback that is invoked when the Promise is settled (fulfilled or rejected). The
     * resolved value cannot be modified from the callback.
     * @param onfinally The callback to execute when the Promise is settled (fulfilled or rejected).
     * @returns A Promise for the completion of the callback.
     */
    finally(onfinally?: (() => void) | undefined | null): $Utils.JsPromise<T>
  }




  /**
   * Fields of the Task model
   */
  interface TaskFieldRefs {
    readonly id: FieldRef<"Task", 'String'>
    readonly title: FieldRef<"Task", 'String'>
    readonly iconUrl: FieldRef<"Task", 'String'>
    readonly description: FieldRef<"Task", 'String'>
    readonly createdAt: FieldRef<"Task", 'DateTime'>
    readonly updatedAt: FieldRef<"Task", 'DateTime'>
  }
    

  // Custom InputTypes
  /**
   * Task findUnique
   */
  export type TaskFindUniqueArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Task
     */
    select?: TaskSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Task
     */
    omit?: TaskOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TaskInclude<ExtArgs> | null
    /**
     * Filter, which Task to fetch.
     */
    where: TaskWhereUniqueInput
  }

  /**
   * Task findUniqueOrThrow
   */
  export type TaskFindUniqueOrThrowArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Task
     */
    select?: TaskSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Task
     */
    omit?: TaskOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TaskInclude<ExtArgs> | null
    /**
     * Filter, which Task to fetch.
     */
    where: TaskWhereUniqueInput
  }

  /**
   * Task findFirst
   */
  export type TaskFindFirstArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Task
     */
    select?: TaskSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Task
     */
    omit?: TaskOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TaskInclude<ExtArgs> | null
    /**
     * Filter, which Task to fetch.
     */
    where?: TaskWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of Tasks to fetch.
     */
    orderBy?: TaskOrderByWithRelationInput | TaskOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the position for searching for Tasks.
     */
    cursor?: TaskWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` Tasks from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` Tasks.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/distinct Distinct Docs}
     * 
     * Filter by unique combinations of Tasks.
     */
    distinct?: TaskScalarFieldEnum | TaskScalarFieldEnum[]
  }

  /**
   * Task findFirstOrThrow
   */
  export type TaskFindFirstOrThrowArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Task
     */
    select?: TaskSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Task
     */
    omit?: TaskOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TaskInclude<ExtArgs> | null
    /**
     * Filter, which Task to fetch.
     */
    where?: TaskWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of Tasks to fetch.
     */
    orderBy?: TaskOrderByWithRelationInput | TaskOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the position for searching for Tasks.
     */
    cursor?: TaskWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` Tasks from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` Tasks.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/distinct Distinct Docs}
     * 
     * Filter by unique combinations of Tasks.
     */
    distinct?: TaskScalarFieldEnum | TaskScalarFieldEnum[]
  }

  /**
   * Task findMany
   */
  export type TaskFindManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Task
     */
    select?: TaskSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Task
     */
    omit?: TaskOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TaskInclude<ExtArgs> | null
    /**
     * Filter, which Tasks to fetch.
     */
    where?: TaskWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of Tasks to fetch.
     */
    orderBy?: TaskOrderByWithRelationInput | TaskOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the position for listing Tasks.
     */
    cursor?: TaskWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` Tasks from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` Tasks.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/distinct Distinct Docs}
     * 
     * Filter by unique combinations of Tasks.
     */
    distinct?: TaskScalarFieldEnum | TaskScalarFieldEnum[]
  }

  /**
   * Task create
   */
  export type TaskCreateArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Task
     */
    select?: TaskSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Task
     */
    omit?: TaskOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TaskInclude<ExtArgs> | null
    /**
     * The data needed to create a Task.
     */
    data: XOR<TaskCreateInput, TaskUncheckedCreateInput>
  }

  /**
   * Task createMany
   */
  export type TaskCreateManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * The data used to create many Tasks.
     */
    data: TaskCreateManyInput | TaskCreateManyInput[]
    skipDuplicates?: boolean
  }

  /**
   * Task createManyAndReturn
   */
  export type TaskCreateManyAndReturnArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Task
     */
    select?: TaskSelectCreateManyAndReturn<ExtArgs> | null
    /**
     * Omit specific fields from the Task
     */
    omit?: TaskOmit<ExtArgs> | null
    /**
     * The data used to create many Tasks.
     */
    data: TaskCreateManyInput | TaskCreateManyInput[]
    skipDuplicates?: boolean
  }

  /**
   * Task update
   */
  export type TaskUpdateArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Task
     */
    select?: TaskSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Task
     */
    omit?: TaskOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TaskInclude<ExtArgs> | null
    /**
     * The data needed to update a Task.
     */
    data: XOR<TaskUpdateInput, TaskUncheckedUpdateInput>
    /**
     * Choose, which Task to update.
     */
    where: TaskWhereUniqueInput
  }

  /**
   * Task updateMany
   */
  export type TaskUpdateManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * The data used to update Tasks.
     */
    data: XOR<TaskUpdateManyMutationInput, TaskUncheckedUpdateManyInput>
    /**
     * Filter which Tasks to update
     */
    where?: TaskWhereInput
    /**
     * Limit how many Tasks to update.
     */
    limit?: number
  }

  /**
   * Task updateManyAndReturn
   */
  export type TaskUpdateManyAndReturnArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Task
     */
    select?: TaskSelectUpdateManyAndReturn<ExtArgs> | null
    /**
     * Omit specific fields from the Task
     */
    omit?: TaskOmit<ExtArgs> | null
    /**
     * The data used to update Tasks.
     */
    data: XOR<TaskUpdateManyMutationInput, TaskUncheckedUpdateManyInput>
    /**
     * Filter which Tasks to update
     */
    where?: TaskWhereInput
    /**
     * Limit how many Tasks to update.
     */
    limit?: number
  }

  /**
   * Task upsert
   */
  export type TaskUpsertArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Task
     */
    select?: TaskSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Task
     */
    omit?: TaskOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TaskInclude<ExtArgs> | null
    /**
     * The filter to search for the Task to update in case it exists.
     */
    where: TaskWhereUniqueInput
    /**
     * In case the Task found by the `where` argument doesn't exist, create a new Task with this data.
     */
    create: XOR<TaskCreateInput, TaskUncheckedCreateInput>
    /**
     * In case the Task was found with the provided `where` argument, update it with this data.
     */
    update: XOR<TaskUpdateInput, TaskUncheckedUpdateInput>
  }

  /**
   * Task delete
   */
  export type TaskDeleteArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Task
     */
    select?: TaskSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Task
     */
    omit?: TaskOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TaskInclude<ExtArgs> | null
    /**
     * Filter which Task to delete.
     */
    where: TaskWhereUniqueInput
  }

  /**
   * Task deleteMany
   */
  export type TaskDeleteManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Filter which Tasks to delete
     */
    where?: TaskWhereInput
    /**
     * Limit how many Tasks to delete.
     */
    limit?: number
  }

  /**
   * Task.tiles
   */
  export type Task$tilesArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Tile
     */
    select?: TileSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Tile
     */
    omit?: TileOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TileInclude<ExtArgs> | null
    where?: TileWhereInput
    orderBy?: TileOrderByWithRelationInput | TileOrderByWithRelationInput[]
    cursor?: TileWhereUniqueInput
    take?: number
    skip?: number
    distinct?: TileScalarFieldEnum | TileScalarFieldEnum[]
  }

  /**
   * Task without action
   */
  export type TaskDefaultArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Task
     */
    select?: TaskSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Task
     */
    omit?: TaskOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TaskInclude<ExtArgs> | null
  }


  /**
   * Model Board
   */

  export type AggregateBoard = {
    _count: BoardCountAggregateOutputType | null
    _avg: BoardAvgAggregateOutputType | null
    _sum: BoardSumAggregateOutputType | null
    _min: BoardMinAggregateOutputType | null
    _max: BoardMaxAggregateOutputType | null
  }

  export type BoardAvgAggregateOutputType = {
    diceRollLimit: number | null
  }

  export type BoardSumAggregateOutputType = {
    diceRollLimit: number | null
  }

  export type BoardMinAggregateOutputType = {
    id: string | null
    title: string | null
    description: string | null
    startDate: Date | null
    endDate: Date | null
    size: $Enums.BoardSize | null
    mode: $Enums.BoardMode | null
    diceRollLimit: number | null
    createdAt: Date | null
    updatedAt: Date | null
  }

  export type BoardMaxAggregateOutputType = {
    id: string | null
    title: string | null
    description: string | null
    startDate: Date | null
    endDate: Date | null
    size: $Enums.BoardSize | null
    mode: $Enums.BoardMode | null
    diceRollLimit: number | null
    createdAt: Date | null
    updatedAt: Date | null
  }

  export type BoardCountAggregateOutputType = {
    id: number
    title: number
    description: number
    startDate: number
    endDate: number
    size: number
    mode: number
    diceRollLimit: number
    createdAt: number
    updatedAt: number
    _all: number
  }


  export type BoardAvgAggregateInputType = {
    diceRollLimit?: true
  }

  export type BoardSumAggregateInputType = {
    diceRollLimit?: true
  }

  export type BoardMinAggregateInputType = {
    id?: true
    title?: true
    description?: true
    startDate?: true
    endDate?: true
    size?: true
    mode?: true
    diceRollLimit?: true
    createdAt?: true
    updatedAt?: true
  }

  export type BoardMaxAggregateInputType = {
    id?: true
    title?: true
    description?: true
    startDate?: true
    endDate?: true
    size?: true
    mode?: true
    diceRollLimit?: true
    createdAt?: true
    updatedAt?: true
  }

  export type BoardCountAggregateInputType = {
    id?: true
    title?: true
    description?: true
    startDate?: true
    endDate?: true
    size?: true
    mode?: true
    diceRollLimit?: true
    createdAt?: true
    updatedAt?: true
    _all?: true
  }

  export type BoardAggregateArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Filter which Board to aggregate.
     */
    where?: BoardWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of Boards to fetch.
     */
    orderBy?: BoardOrderByWithRelationInput | BoardOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the start position
     */
    cursor?: BoardWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` Boards from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` Boards.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Count returned Boards
    **/
    _count?: true | BoardCountAggregateInputType
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Select which fields to average
    **/
    _avg?: BoardAvgAggregateInputType
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Select which fields to sum
    **/
    _sum?: BoardSumAggregateInputType
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Select which fields to find the minimum value
    **/
    _min?: BoardMinAggregateInputType
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Select which fields to find the maximum value
    **/
    _max?: BoardMaxAggregateInputType
  }

  export type GetBoardAggregateType<T extends BoardAggregateArgs> = {
        [P in keyof T & keyof AggregateBoard]: P extends '_count' | 'count'
      ? T[P] extends true
        ? number
        : GetScalarType<T[P], AggregateBoard[P]>
      : GetScalarType<T[P], AggregateBoard[P]>
  }




  export type BoardGroupByArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    where?: BoardWhereInput
    orderBy?: BoardOrderByWithAggregationInput | BoardOrderByWithAggregationInput[]
    by: BoardScalarFieldEnum[] | BoardScalarFieldEnum
    having?: BoardScalarWhereWithAggregatesInput
    take?: number
    skip?: number
    _count?: BoardCountAggregateInputType | true
    _avg?: BoardAvgAggregateInputType
    _sum?: BoardSumAggregateInputType
    _min?: BoardMinAggregateInputType
    _max?: BoardMaxAggregateInputType
  }

  export type BoardGroupByOutputType = {
    id: string
    title: string
    description: string | null
    startDate: Date | null
    endDate: Date | null
    size: $Enums.BoardSize
    mode: $Enums.BoardMode
    diceRollLimit: number | null
    createdAt: Date
    updatedAt: Date
    _count: BoardCountAggregateOutputType | null
    _avg: BoardAvgAggregateOutputType | null
    _sum: BoardSumAggregateOutputType | null
    _min: BoardMinAggregateOutputType | null
    _max: BoardMaxAggregateOutputType | null
  }

  type GetBoardGroupByPayload<T extends BoardGroupByArgs> = Prisma.PrismaPromise<
    Array<
      PickEnumerable<BoardGroupByOutputType, T['by']> &
        {
          [P in ((keyof T) & (keyof BoardGroupByOutputType))]: P extends '_count'
            ? T[P] extends boolean
              ? number
              : GetScalarType<T[P], BoardGroupByOutputType[P]>
            : GetScalarType<T[P], BoardGroupByOutputType[P]>
        }
      >
    >


  export type BoardSelect<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetSelect<{
    id?: boolean
    title?: boolean
    description?: boolean
    startDate?: boolean
    endDate?: boolean
    size?: boolean
    mode?: boolean
    diceRollLimit?: boolean
    createdAt?: boolean
    updatedAt?: boolean
    authors?: boolean | Board$authorsArgs<ExtArgs>
    tiles?: boolean | Board$tilesArgs<ExtArgs>
    playerBoards?: boolean | Board$playerBoardsArgs<ExtArgs>
    boardTeams?: boolean | Board$boardTeamsArgs<ExtArgs>
    _count?: boolean | BoardCountOutputTypeDefaultArgs<ExtArgs>
  }, ExtArgs["result"]["board"]>

  export type BoardSelectCreateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetSelect<{
    id?: boolean
    title?: boolean
    description?: boolean
    startDate?: boolean
    endDate?: boolean
    size?: boolean
    mode?: boolean
    diceRollLimit?: boolean
    createdAt?: boolean
    updatedAt?: boolean
  }, ExtArgs["result"]["board"]>

  export type BoardSelectUpdateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetSelect<{
    id?: boolean
    title?: boolean
    description?: boolean
    startDate?: boolean
    endDate?: boolean
    size?: boolean
    mode?: boolean
    diceRollLimit?: boolean
    createdAt?: boolean
    updatedAt?: boolean
  }, ExtArgs["result"]["board"]>

  export type BoardSelectScalar = {
    id?: boolean
    title?: boolean
    description?: boolean
    startDate?: boolean
    endDate?: boolean
    size?: boolean
    mode?: boolean
    diceRollLimit?: boolean
    createdAt?: boolean
    updatedAt?: boolean
  }

  export type BoardOmit<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetOmit<"id" | "title" | "description" | "startDate" | "endDate" | "size" | "mode" | "diceRollLimit" | "createdAt" | "updatedAt", ExtArgs["result"]["board"]>
  export type BoardInclude<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    authors?: boolean | Board$authorsArgs<ExtArgs>
    tiles?: boolean | Board$tilesArgs<ExtArgs>
    playerBoards?: boolean | Board$playerBoardsArgs<ExtArgs>
    boardTeams?: boolean | Board$boardTeamsArgs<ExtArgs>
    _count?: boolean | BoardCountOutputTypeDefaultArgs<ExtArgs>
  }
  export type BoardIncludeCreateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {}
  export type BoardIncludeUpdateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {}

  export type $BoardPayload<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    name: "Board"
    objects: {
      authors: Prisma.$BoardAuthorPayload<ExtArgs>[]
      tiles: Prisma.$TilePayload<ExtArgs>[]
      playerBoards: Prisma.$PlayerBoardPayload<ExtArgs>[]
      boardTeams: Prisma.$BoardTeamPayload<ExtArgs>[]
    }
    scalars: $Extensions.GetPayloadResult<{
      id: string
      title: string
      description: string | null
      startDate: Date | null
      endDate: Date | null
      size: $Enums.BoardSize
      mode: $Enums.BoardMode
      diceRollLimit: number | null
      createdAt: Date
      updatedAt: Date
    }, ExtArgs["result"]["board"]>
    composites: {}
  }

  type BoardGetPayload<S extends boolean | null | undefined | BoardDefaultArgs> = $Result.GetResult<Prisma.$BoardPayload, S>

  type BoardCountArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> =
    Omit<BoardFindManyArgs, 'select' | 'include' | 'distinct' | 'omit'> & {
      select?: BoardCountAggregateInputType | true
    }

  export interface BoardDelegate<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs, GlobalOmitOptions = {}> {
    [K: symbol]: { types: Prisma.TypeMap<ExtArgs>['model']['Board'], meta: { name: 'Board' } }
    /**
     * Find zero or one Board that matches the filter.
     * @param {BoardFindUniqueArgs} args - Arguments to find a Board
     * @example
     * // Get one Board
     * const board = await prisma.board.findUnique({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findUnique<T extends BoardFindUniqueArgs>(args: SelectSubset<T, BoardFindUniqueArgs<ExtArgs>>): Prisma__BoardClient<$Result.GetResult<Prisma.$BoardPayload<ExtArgs>, T, "findUnique", GlobalOmitOptions> | null, null, ExtArgs, GlobalOmitOptions>

    /**
     * Find one Board that matches the filter or throw an error with `error.code='P2025'`
     * if no matches were found.
     * @param {BoardFindUniqueOrThrowArgs} args - Arguments to find a Board
     * @example
     * // Get one Board
     * const board = await prisma.board.findUniqueOrThrow({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findUniqueOrThrow<T extends BoardFindUniqueOrThrowArgs>(args: SelectSubset<T, BoardFindUniqueOrThrowArgs<ExtArgs>>): Prisma__BoardClient<$Result.GetResult<Prisma.$BoardPayload<ExtArgs>, T, "findUniqueOrThrow", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Find the first Board that matches the filter.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {BoardFindFirstArgs} args - Arguments to find a Board
     * @example
     * // Get one Board
     * const board = await prisma.board.findFirst({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findFirst<T extends BoardFindFirstArgs>(args?: SelectSubset<T, BoardFindFirstArgs<ExtArgs>>): Prisma__BoardClient<$Result.GetResult<Prisma.$BoardPayload<ExtArgs>, T, "findFirst", GlobalOmitOptions> | null, null, ExtArgs, GlobalOmitOptions>

    /**
     * Find the first Board that matches the filter or
     * throw `PrismaKnownClientError` with `P2025` code if no matches were found.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {BoardFindFirstOrThrowArgs} args - Arguments to find a Board
     * @example
     * // Get one Board
     * const board = await prisma.board.findFirstOrThrow({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findFirstOrThrow<T extends BoardFindFirstOrThrowArgs>(args?: SelectSubset<T, BoardFindFirstOrThrowArgs<ExtArgs>>): Prisma__BoardClient<$Result.GetResult<Prisma.$BoardPayload<ExtArgs>, T, "findFirstOrThrow", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Find zero or more Boards that matches the filter.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {BoardFindManyArgs} args - Arguments to filter and select certain fields only.
     * @example
     * // Get all Boards
     * const boards = await prisma.board.findMany()
     * 
     * // Get first 10 Boards
     * const boards = await prisma.board.findMany({ take: 10 })
     * 
     * // Only select the `id`
     * const boardWithIdOnly = await prisma.board.findMany({ select: { id: true } })
     * 
     */
    findMany<T extends BoardFindManyArgs>(args?: SelectSubset<T, BoardFindManyArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$BoardPayload<ExtArgs>, T, "findMany", GlobalOmitOptions>>

    /**
     * Create a Board.
     * @param {BoardCreateArgs} args - Arguments to create a Board.
     * @example
     * // Create one Board
     * const Board = await prisma.board.create({
     *   data: {
     *     // ... data to create a Board
     *   }
     * })
     * 
     */
    create<T extends BoardCreateArgs>(args: SelectSubset<T, BoardCreateArgs<ExtArgs>>): Prisma__BoardClient<$Result.GetResult<Prisma.$BoardPayload<ExtArgs>, T, "create", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Create many Boards.
     * @param {BoardCreateManyArgs} args - Arguments to create many Boards.
     * @example
     * // Create many Boards
     * const board = await prisma.board.createMany({
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     *     
     */
    createMany<T extends BoardCreateManyArgs>(args?: SelectSubset<T, BoardCreateManyArgs<ExtArgs>>): Prisma.PrismaPromise<BatchPayload>

    /**
     * Create many Boards and returns the data saved in the database.
     * @param {BoardCreateManyAndReturnArgs} args - Arguments to create many Boards.
     * @example
     * // Create many Boards
     * const board = await prisma.board.createManyAndReturn({
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * 
     * // Create many Boards and only return the `id`
     * const boardWithIdOnly = await prisma.board.createManyAndReturn({
     *   select: { id: true },
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * 
     */
    createManyAndReturn<T extends BoardCreateManyAndReturnArgs>(args?: SelectSubset<T, BoardCreateManyAndReturnArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$BoardPayload<ExtArgs>, T, "createManyAndReturn", GlobalOmitOptions>>

    /**
     * Delete a Board.
     * @param {BoardDeleteArgs} args - Arguments to delete one Board.
     * @example
     * // Delete one Board
     * const Board = await prisma.board.delete({
     *   where: {
     *     // ... filter to delete one Board
     *   }
     * })
     * 
     */
    delete<T extends BoardDeleteArgs>(args: SelectSubset<T, BoardDeleteArgs<ExtArgs>>): Prisma__BoardClient<$Result.GetResult<Prisma.$BoardPayload<ExtArgs>, T, "delete", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Update one Board.
     * @param {BoardUpdateArgs} args - Arguments to update one Board.
     * @example
     * // Update one Board
     * const board = await prisma.board.update({
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: {
     *     // ... provide data here
     *   }
     * })
     * 
     */
    update<T extends BoardUpdateArgs>(args: SelectSubset<T, BoardUpdateArgs<ExtArgs>>): Prisma__BoardClient<$Result.GetResult<Prisma.$BoardPayload<ExtArgs>, T, "update", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Delete zero or more Boards.
     * @param {BoardDeleteManyArgs} args - Arguments to filter Boards to delete.
     * @example
     * // Delete a few Boards
     * const { count } = await prisma.board.deleteMany({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     * 
     */
    deleteMany<T extends BoardDeleteManyArgs>(args?: SelectSubset<T, BoardDeleteManyArgs<ExtArgs>>): Prisma.PrismaPromise<BatchPayload>

    /**
     * Update zero or more Boards.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {BoardUpdateManyArgs} args - Arguments to update one or more rows.
     * @example
     * // Update many Boards
     * const board = await prisma.board.updateMany({
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: {
     *     // ... provide data here
     *   }
     * })
     * 
     */
    updateMany<T extends BoardUpdateManyArgs>(args: SelectSubset<T, BoardUpdateManyArgs<ExtArgs>>): Prisma.PrismaPromise<BatchPayload>

    /**
     * Update zero or more Boards and returns the data updated in the database.
     * @param {BoardUpdateManyAndReturnArgs} args - Arguments to update many Boards.
     * @example
     * // Update many Boards
     * const board = await prisma.board.updateManyAndReturn({
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * 
     * // Update zero or more Boards and only return the `id`
     * const boardWithIdOnly = await prisma.board.updateManyAndReturn({
     *   select: { id: true },
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * 
     */
    updateManyAndReturn<T extends BoardUpdateManyAndReturnArgs>(args: SelectSubset<T, BoardUpdateManyAndReturnArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$BoardPayload<ExtArgs>, T, "updateManyAndReturn", GlobalOmitOptions>>

    /**
     * Create or update one Board.
     * @param {BoardUpsertArgs} args - Arguments to update or create a Board.
     * @example
     * // Update or create a Board
     * const board = await prisma.board.upsert({
     *   create: {
     *     // ... data to create a Board
     *   },
     *   update: {
     *     // ... in case it already exists, update
     *   },
     *   where: {
     *     // ... the filter for the Board we want to update
     *   }
     * })
     */
    upsert<T extends BoardUpsertArgs>(args: SelectSubset<T, BoardUpsertArgs<ExtArgs>>): Prisma__BoardClient<$Result.GetResult<Prisma.$BoardPayload<ExtArgs>, T, "upsert", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>


    /**
     * Count the number of Boards.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {BoardCountArgs} args - Arguments to filter Boards to count.
     * @example
     * // Count the number of Boards
     * const count = await prisma.board.count({
     *   where: {
     *     // ... the filter for the Boards we want to count
     *   }
     * })
    **/
    count<T extends BoardCountArgs>(
      args?: Subset<T, BoardCountArgs>,
    ): Prisma.PrismaPromise<
      T extends $Utils.Record<'select', any>
        ? T['select'] extends true
          ? number
          : GetScalarType<T['select'], BoardCountAggregateOutputType>
        : number
    >

    /**
     * Allows you to perform aggregations operations on a Board.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {BoardAggregateArgs} args - Select which aggregations you would like to apply and on what fields.
     * @example
     * // Ordered by age ascending
     * // Where email contains prisma.io
     * // Limited to the 10 users
     * const aggregations = await prisma.user.aggregate({
     *   _avg: {
     *     age: true,
     *   },
     *   where: {
     *     email: {
     *       contains: "prisma.io",
     *     },
     *   },
     *   orderBy: {
     *     age: "asc",
     *   },
     *   take: 10,
     * })
    **/
    aggregate<T extends BoardAggregateArgs>(args: Subset<T, BoardAggregateArgs>): Prisma.PrismaPromise<GetBoardAggregateType<T>>

    /**
     * Group by Board.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {BoardGroupByArgs} args - Group by arguments.
     * @example
     * // Group by city, order by createdAt, get count
     * const result = await prisma.user.groupBy({
     *   by: ['city', 'createdAt'],
     *   orderBy: {
     *     createdAt: true
     *   },
     *   _count: {
     *     _all: true
     *   },
     * })
     * 
    **/
    groupBy<
      T extends BoardGroupByArgs,
      HasSelectOrTake extends Or<
        Extends<'skip', Keys<T>>,
        Extends<'take', Keys<T>>
      >,
      OrderByArg extends True extends HasSelectOrTake
        ? { orderBy: BoardGroupByArgs['orderBy'] }
        : { orderBy?: BoardGroupByArgs['orderBy'] },
      OrderFields extends ExcludeUnderscoreKeys<Keys<MaybeTupleToUnion<T['orderBy']>>>,
      ByFields extends MaybeTupleToUnion<T['by']>,
      ByValid extends Has<ByFields, OrderFields>,
      HavingFields extends GetHavingFields<T['having']>,
      HavingValid extends Has<ByFields, HavingFields>,
      ByEmpty extends T['by'] extends never[] ? True : False,
      InputErrors extends ByEmpty extends True
      ? `Error: "by" must not be empty.`
      : HavingValid extends False
      ? {
          [P in HavingFields]: P extends ByFields
            ? never
            : P extends string
            ? `Error: Field "${P}" used in "having" needs to be provided in "by".`
            : [
                Error,
                'Field ',
                P,
                ` in "having" needs to be provided in "by"`,
              ]
        }[HavingFields]
      : 'take' extends Keys<T>
      ? 'orderBy' extends Keys<T>
        ? ByValid extends True
          ? {}
          : {
              [P in OrderFields]: P extends ByFields
                ? never
                : `Error: Field "${P}" in "orderBy" needs to be provided in "by"`
            }[OrderFields]
        : 'Error: If you provide "take", you also need to provide "orderBy"'
      : 'skip' extends Keys<T>
      ? 'orderBy' extends Keys<T>
        ? ByValid extends True
          ? {}
          : {
              [P in OrderFields]: P extends ByFields
                ? never
                : `Error: Field "${P}" in "orderBy" needs to be provided in "by"`
            }[OrderFields]
        : 'Error: If you provide "skip", you also need to provide "orderBy"'
      : ByValid extends True
      ? {}
      : {
          [P in OrderFields]: P extends ByFields
            ? never
            : `Error: Field "${P}" in "orderBy" needs to be provided in "by"`
        }[OrderFields]
    >(args: SubsetIntersection<T, BoardGroupByArgs, OrderByArg> & InputErrors): {} extends InputErrors ? GetBoardGroupByPayload<T> : Prisma.PrismaPromise<InputErrors>
  /**
   * Fields of the Board model
   */
  readonly fields: BoardFieldRefs;
  }

  /**
   * The delegate class that acts as a "Promise-like" for Board.
   * Why is this prefixed with `Prisma__`?
   * Because we want to prevent naming conflicts as mentioned in
   * https://github.com/prisma/prisma-client-js/issues/707
   */
  export interface Prisma__BoardClient<T, Null = never, ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs, GlobalOmitOptions = {}> extends Prisma.PrismaPromise<T> {
    readonly [Symbol.toStringTag]: "PrismaPromise"
    authors<T extends Board$authorsArgs<ExtArgs> = {}>(args?: Subset<T, Board$authorsArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$BoardAuthorPayload<ExtArgs>, T, "findMany", GlobalOmitOptions> | Null>
    tiles<T extends Board$tilesArgs<ExtArgs> = {}>(args?: Subset<T, Board$tilesArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$TilePayload<ExtArgs>, T, "findMany", GlobalOmitOptions> | Null>
    playerBoards<T extends Board$playerBoardsArgs<ExtArgs> = {}>(args?: Subset<T, Board$playerBoardsArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$PlayerBoardPayload<ExtArgs>, T, "findMany", GlobalOmitOptions> | Null>
    boardTeams<T extends Board$boardTeamsArgs<ExtArgs> = {}>(args?: Subset<T, Board$boardTeamsArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$BoardTeamPayload<ExtArgs>, T, "findMany", GlobalOmitOptions> | Null>
    /**
     * Attaches callbacks for the resolution and/or rejection of the Promise.
     * @param onfulfilled The callback to execute when the Promise is resolved.
     * @param onrejected The callback to execute when the Promise is rejected.
     * @returns A Promise for the completion of which ever callback is executed.
     */
    then<TResult1 = T, TResult2 = never>(onfulfilled?: ((value: T) => TResult1 | PromiseLike<TResult1>) | undefined | null, onrejected?: ((reason: any) => TResult2 | PromiseLike<TResult2>) | undefined | null): $Utils.JsPromise<TResult1 | TResult2>
    /**
     * Attaches a callback for only the rejection of the Promise.
     * @param onrejected The callback to execute when the Promise is rejected.
     * @returns A Promise for the completion of the callback.
     */
    catch<TResult = never>(onrejected?: ((reason: any) => TResult | PromiseLike<TResult>) | undefined | null): $Utils.JsPromise<T | TResult>
    /**
     * Attaches a callback that is invoked when the Promise is settled (fulfilled or rejected). The
     * resolved value cannot be modified from the callback.
     * @param onfinally The callback to execute when the Promise is settled (fulfilled or rejected).
     * @returns A Promise for the completion of the callback.
     */
    finally(onfinally?: (() => void) | undefined | null): $Utils.JsPromise<T>
  }




  /**
   * Fields of the Board model
   */
  interface BoardFieldRefs {
    readonly id: FieldRef<"Board", 'String'>
    readonly title: FieldRef<"Board", 'String'>
    readonly description: FieldRef<"Board", 'String'>
    readonly startDate: FieldRef<"Board", 'DateTime'>
    readonly endDate: FieldRef<"Board", 'DateTime'>
    readonly size: FieldRef<"Board", 'BoardSize'>
    readonly mode: FieldRef<"Board", 'BoardMode'>
    readonly diceRollLimit: FieldRef<"Board", 'Int'>
    readonly createdAt: FieldRef<"Board", 'DateTime'>
    readonly updatedAt: FieldRef<"Board", 'DateTime'>
  }
    

  // Custom InputTypes
  /**
   * Board findUnique
   */
  export type BoardFindUniqueArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Board
     */
    select?: BoardSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Board
     */
    omit?: BoardOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: BoardInclude<ExtArgs> | null
    /**
     * Filter, which Board to fetch.
     */
    where: BoardWhereUniqueInput
  }

  /**
   * Board findUniqueOrThrow
   */
  export type BoardFindUniqueOrThrowArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Board
     */
    select?: BoardSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Board
     */
    omit?: BoardOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: BoardInclude<ExtArgs> | null
    /**
     * Filter, which Board to fetch.
     */
    where: BoardWhereUniqueInput
  }

  /**
   * Board findFirst
   */
  export type BoardFindFirstArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Board
     */
    select?: BoardSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Board
     */
    omit?: BoardOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: BoardInclude<ExtArgs> | null
    /**
     * Filter, which Board to fetch.
     */
    where?: BoardWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of Boards to fetch.
     */
    orderBy?: BoardOrderByWithRelationInput | BoardOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the position for searching for Boards.
     */
    cursor?: BoardWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` Boards from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` Boards.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/distinct Distinct Docs}
     * 
     * Filter by unique combinations of Boards.
     */
    distinct?: BoardScalarFieldEnum | BoardScalarFieldEnum[]
  }

  /**
   * Board findFirstOrThrow
   */
  export type BoardFindFirstOrThrowArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Board
     */
    select?: BoardSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Board
     */
    omit?: BoardOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: BoardInclude<ExtArgs> | null
    /**
     * Filter, which Board to fetch.
     */
    where?: BoardWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of Boards to fetch.
     */
    orderBy?: BoardOrderByWithRelationInput | BoardOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the position for searching for Boards.
     */
    cursor?: BoardWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` Boards from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` Boards.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/distinct Distinct Docs}
     * 
     * Filter by unique combinations of Boards.
     */
    distinct?: BoardScalarFieldEnum | BoardScalarFieldEnum[]
  }

  /**
   * Board findMany
   */
  export type BoardFindManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Board
     */
    select?: BoardSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Board
     */
    omit?: BoardOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: BoardInclude<ExtArgs> | null
    /**
     * Filter, which Boards to fetch.
     */
    where?: BoardWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of Boards to fetch.
     */
    orderBy?: BoardOrderByWithRelationInput | BoardOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the position for listing Boards.
     */
    cursor?: BoardWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` Boards from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` Boards.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/distinct Distinct Docs}
     * 
     * Filter by unique combinations of Boards.
     */
    distinct?: BoardScalarFieldEnum | BoardScalarFieldEnum[]
  }

  /**
   * Board create
   */
  export type BoardCreateArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Board
     */
    select?: BoardSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Board
     */
    omit?: BoardOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: BoardInclude<ExtArgs> | null
    /**
     * The data needed to create a Board.
     */
    data: XOR<BoardCreateInput, BoardUncheckedCreateInput>
  }

  /**
   * Board createMany
   */
  export type BoardCreateManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * The data used to create many Boards.
     */
    data: BoardCreateManyInput | BoardCreateManyInput[]
    skipDuplicates?: boolean
  }

  /**
   * Board createManyAndReturn
   */
  export type BoardCreateManyAndReturnArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Board
     */
    select?: BoardSelectCreateManyAndReturn<ExtArgs> | null
    /**
     * Omit specific fields from the Board
     */
    omit?: BoardOmit<ExtArgs> | null
    /**
     * The data used to create many Boards.
     */
    data: BoardCreateManyInput | BoardCreateManyInput[]
    skipDuplicates?: boolean
  }

  /**
   * Board update
   */
  export type BoardUpdateArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Board
     */
    select?: BoardSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Board
     */
    omit?: BoardOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: BoardInclude<ExtArgs> | null
    /**
     * The data needed to update a Board.
     */
    data: XOR<BoardUpdateInput, BoardUncheckedUpdateInput>
    /**
     * Choose, which Board to update.
     */
    where: BoardWhereUniqueInput
  }

  /**
   * Board updateMany
   */
  export type BoardUpdateManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * The data used to update Boards.
     */
    data: XOR<BoardUpdateManyMutationInput, BoardUncheckedUpdateManyInput>
    /**
     * Filter which Boards to update
     */
    where?: BoardWhereInput
    /**
     * Limit how many Boards to update.
     */
    limit?: number
  }

  /**
   * Board updateManyAndReturn
   */
  export type BoardUpdateManyAndReturnArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Board
     */
    select?: BoardSelectUpdateManyAndReturn<ExtArgs> | null
    /**
     * Omit specific fields from the Board
     */
    omit?: BoardOmit<ExtArgs> | null
    /**
     * The data used to update Boards.
     */
    data: XOR<BoardUpdateManyMutationInput, BoardUncheckedUpdateManyInput>
    /**
     * Filter which Boards to update
     */
    where?: BoardWhereInput
    /**
     * Limit how many Boards to update.
     */
    limit?: number
  }

  /**
   * Board upsert
   */
  export type BoardUpsertArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Board
     */
    select?: BoardSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Board
     */
    omit?: BoardOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: BoardInclude<ExtArgs> | null
    /**
     * The filter to search for the Board to update in case it exists.
     */
    where: BoardWhereUniqueInput
    /**
     * In case the Board found by the `where` argument doesn't exist, create a new Board with this data.
     */
    create: XOR<BoardCreateInput, BoardUncheckedCreateInput>
    /**
     * In case the Board was found with the provided `where` argument, update it with this data.
     */
    update: XOR<BoardUpdateInput, BoardUncheckedUpdateInput>
  }

  /**
   * Board delete
   */
  export type BoardDeleteArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Board
     */
    select?: BoardSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Board
     */
    omit?: BoardOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: BoardInclude<ExtArgs> | null
    /**
     * Filter which Board to delete.
     */
    where: BoardWhereUniqueInput
  }

  /**
   * Board deleteMany
   */
  export type BoardDeleteManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Filter which Boards to delete
     */
    where?: BoardWhereInput
    /**
     * Limit how many Boards to delete.
     */
    limit?: number
  }

  /**
   * Board.authors
   */
  export type Board$authorsArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the BoardAuthor
     */
    select?: BoardAuthorSelect<ExtArgs> | null
    /**
     * Omit specific fields from the BoardAuthor
     */
    omit?: BoardAuthorOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: BoardAuthorInclude<ExtArgs> | null
    where?: BoardAuthorWhereInput
    orderBy?: BoardAuthorOrderByWithRelationInput | BoardAuthorOrderByWithRelationInput[]
    cursor?: BoardAuthorWhereUniqueInput
    take?: number
    skip?: number
    distinct?: BoardAuthorScalarFieldEnum | BoardAuthorScalarFieldEnum[]
  }

  /**
   * Board.tiles
   */
  export type Board$tilesArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Tile
     */
    select?: TileSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Tile
     */
    omit?: TileOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TileInclude<ExtArgs> | null
    where?: TileWhereInput
    orderBy?: TileOrderByWithRelationInput | TileOrderByWithRelationInput[]
    cursor?: TileWhereUniqueInput
    take?: number
    skip?: number
    distinct?: TileScalarFieldEnum | TileScalarFieldEnum[]
  }

  /**
   * Board.playerBoards
   */
  export type Board$playerBoardsArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the PlayerBoard
     */
    select?: PlayerBoardSelect<ExtArgs> | null
    /**
     * Omit specific fields from the PlayerBoard
     */
    omit?: PlayerBoardOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: PlayerBoardInclude<ExtArgs> | null
    where?: PlayerBoardWhereInput
    orderBy?: PlayerBoardOrderByWithRelationInput | PlayerBoardOrderByWithRelationInput[]
    cursor?: PlayerBoardWhereUniqueInput
    take?: number
    skip?: number
    distinct?: PlayerBoardScalarFieldEnum | PlayerBoardScalarFieldEnum[]
  }

  /**
   * Board.boardTeams
   */
  export type Board$boardTeamsArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the BoardTeam
     */
    select?: BoardTeamSelect<ExtArgs> | null
    /**
     * Omit specific fields from the BoardTeam
     */
    omit?: BoardTeamOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: BoardTeamInclude<ExtArgs> | null
    where?: BoardTeamWhereInput
    orderBy?: BoardTeamOrderByWithRelationInput | BoardTeamOrderByWithRelationInput[]
    cursor?: BoardTeamWhereUniqueInput
    take?: number
    skip?: number
    distinct?: BoardTeamScalarFieldEnum | BoardTeamScalarFieldEnum[]
  }

  /**
   * Board without action
   */
  export type BoardDefaultArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Board
     */
    select?: BoardSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Board
     */
    omit?: BoardOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: BoardInclude<ExtArgs> | null
  }


  /**
   * Model BoardTeam
   */

  export type AggregateBoardTeam = {
    _count: BoardTeamCountAggregateOutputType | null
    _min: BoardTeamMinAggregateOutputType | null
    _max: BoardTeamMaxAggregateOutputType | null
  }

  export type BoardTeamMinAggregateOutputType = {
    id: string | null
    boardId: string | null
    teamId: string | null
    createdAt: Date | null
  }

  export type BoardTeamMaxAggregateOutputType = {
    id: string | null
    boardId: string | null
    teamId: string | null
    createdAt: Date | null
  }

  export type BoardTeamCountAggregateOutputType = {
    id: number
    boardId: number
    teamId: number
    createdAt: number
    _all: number
  }


  export type BoardTeamMinAggregateInputType = {
    id?: true
    boardId?: true
    teamId?: true
    createdAt?: true
  }

  export type BoardTeamMaxAggregateInputType = {
    id?: true
    boardId?: true
    teamId?: true
    createdAt?: true
  }

  export type BoardTeamCountAggregateInputType = {
    id?: true
    boardId?: true
    teamId?: true
    createdAt?: true
    _all?: true
  }

  export type BoardTeamAggregateArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Filter which BoardTeam to aggregate.
     */
    where?: BoardTeamWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of BoardTeams to fetch.
     */
    orderBy?: BoardTeamOrderByWithRelationInput | BoardTeamOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the start position
     */
    cursor?: BoardTeamWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` BoardTeams from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` BoardTeams.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Count returned BoardTeams
    **/
    _count?: true | BoardTeamCountAggregateInputType
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Select which fields to find the minimum value
    **/
    _min?: BoardTeamMinAggregateInputType
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Select which fields to find the maximum value
    **/
    _max?: BoardTeamMaxAggregateInputType
  }

  export type GetBoardTeamAggregateType<T extends BoardTeamAggregateArgs> = {
        [P in keyof T & keyof AggregateBoardTeam]: P extends '_count' | 'count'
      ? T[P] extends true
        ? number
        : GetScalarType<T[P], AggregateBoardTeam[P]>
      : GetScalarType<T[P], AggregateBoardTeam[P]>
  }




  export type BoardTeamGroupByArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    where?: BoardTeamWhereInput
    orderBy?: BoardTeamOrderByWithAggregationInput | BoardTeamOrderByWithAggregationInput[]
    by: BoardTeamScalarFieldEnum[] | BoardTeamScalarFieldEnum
    having?: BoardTeamScalarWhereWithAggregatesInput
    take?: number
    skip?: number
    _count?: BoardTeamCountAggregateInputType | true
    _min?: BoardTeamMinAggregateInputType
    _max?: BoardTeamMaxAggregateInputType
  }

  export type BoardTeamGroupByOutputType = {
    id: string
    boardId: string
    teamId: string
    createdAt: Date
    _count: BoardTeamCountAggregateOutputType | null
    _min: BoardTeamMinAggregateOutputType | null
    _max: BoardTeamMaxAggregateOutputType | null
  }

  type GetBoardTeamGroupByPayload<T extends BoardTeamGroupByArgs> = Prisma.PrismaPromise<
    Array<
      PickEnumerable<BoardTeamGroupByOutputType, T['by']> &
        {
          [P in ((keyof T) & (keyof BoardTeamGroupByOutputType))]: P extends '_count'
            ? T[P] extends boolean
              ? number
              : GetScalarType<T[P], BoardTeamGroupByOutputType[P]>
            : GetScalarType<T[P], BoardTeamGroupByOutputType[P]>
        }
      >
    >


  export type BoardTeamSelect<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetSelect<{
    id?: boolean
    boardId?: boolean
    teamId?: boolean
    createdAt?: boolean
    board?: boolean | BoardDefaultArgs<ExtArgs>
    team?: boolean | TeamDefaultArgs<ExtArgs>
  }, ExtArgs["result"]["boardTeam"]>

  export type BoardTeamSelectCreateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetSelect<{
    id?: boolean
    boardId?: boolean
    teamId?: boolean
    createdAt?: boolean
    board?: boolean | BoardDefaultArgs<ExtArgs>
    team?: boolean | TeamDefaultArgs<ExtArgs>
  }, ExtArgs["result"]["boardTeam"]>

  export type BoardTeamSelectUpdateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetSelect<{
    id?: boolean
    boardId?: boolean
    teamId?: boolean
    createdAt?: boolean
    board?: boolean | BoardDefaultArgs<ExtArgs>
    team?: boolean | TeamDefaultArgs<ExtArgs>
  }, ExtArgs["result"]["boardTeam"]>

  export type BoardTeamSelectScalar = {
    id?: boolean
    boardId?: boolean
    teamId?: boolean
    createdAt?: boolean
  }

  export type BoardTeamOmit<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetOmit<"id" | "boardId" | "teamId" | "createdAt", ExtArgs["result"]["boardTeam"]>
  export type BoardTeamInclude<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    board?: boolean | BoardDefaultArgs<ExtArgs>
    team?: boolean | TeamDefaultArgs<ExtArgs>
  }
  export type BoardTeamIncludeCreateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    board?: boolean | BoardDefaultArgs<ExtArgs>
    team?: boolean | TeamDefaultArgs<ExtArgs>
  }
  export type BoardTeamIncludeUpdateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    board?: boolean | BoardDefaultArgs<ExtArgs>
    team?: boolean | TeamDefaultArgs<ExtArgs>
  }

  export type $BoardTeamPayload<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    name: "BoardTeam"
    objects: {
      board: Prisma.$BoardPayload<ExtArgs>
      team: Prisma.$TeamPayload<ExtArgs>
    }
    scalars: $Extensions.GetPayloadResult<{
      id: string
      boardId: string
      teamId: string
      createdAt: Date
    }, ExtArgs["result"]["boardTeam"]>
    composites: {}
  }

  type BoardTeamGetPayload<S extends boolean | null | undefined | BoardTeamDefaultArgs> = $Result.GetResult<Prisma.$BoardTeamPayload, S>

  type BoardTeamCountArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> =
    Omit<BoardTeamFindManyArgs, 'select' | 'include' | 'distinct' | 'omit'> & {
      select?: BoardTeamCountAggregateInputType | true
    }

  export interface BoardTeamDelegate<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs, GlobalOmitOptions = {}> {
    [K: symbol]: { types: Prisma.TypeMap<ExtArgs>['model']['BoardTeam'], meta: { name: 'BoardTeam' } }
    /**
     * Find zero or one BoardTeam that matches the filter.
     * @param {BoardTeamFindUniqueArgs} args - Arguments to find a BoardTeam
     * @example
     * // Get one BoardTeam
     * const boardTeam = await prisma.boardTeam.findUnique({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findUnique<T extends BoardTeamFindUniqueArgs>(args: SelectSubset<T, BoardTeamFindUniqueArgs<ExtArgs>>): Prisma__BoardTeamClient<$Result.GetResult<Prisma.$BoardTeamPayload<ExtArgs>, T, "findUnique", GlobalOmitOptions> | null, null, ExtArgs, GlobalOmitOptions>

    /**
     * Find one BoardTeam that matches the filter or throw an error with `error.code='P2025'`
     * if no matches were found.
     * @param {BoardTeamFindUniqueOrThrowArgs} args - Arguments to find a BoardTeam
     * @example
     * // Get one BoardTeam
     * const boardTeam = await prisma.boardTeam.findUniqueOrThrow({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findUniqueOrThrow<T extends BoardTeamFindUniqueOrThrowArgs>(args: SelectSubset<T, BoardTeamFindUniqueOrThrowArgs<ExtArgs>>): Prisma__BoardTeamClient<$Result.GetResult<Prisma.$BoardTeamPayload<ExtArgs>, T, "findUniqueOrThrow", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Find the first BoardTeam that matches the filter.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {BoardTeamFindFirstArgs} args - Arguments to find a BoardTeam
     * @example
     * // Get one BoardTeam
     * const boardTeam = await prisma.boardTeam.findFirst({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findFirst<T extends BoardTeamFindFirstArgs>(args?: SelectSubset<T, BoardTeamFindFirstArgs<ExtArgs>>): Prisma__BoardTeamClient<$Result.GetResult<Prisma.$BoardTeamPayload<ExtArgs>, T, "findFirst", GlobalOmitOptions> | null, null, ExtArgs, GlobalOmitOptions>

    /**
     * Find the first BoardTeam that matches the filter or
     * throw `PrismaKnownClientError` with `P2025` code if no matches were found.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {BoardTeamFindFirstOrThrowArgs} args - Arguments to find a BoardTeam
     * @example
     * // Get one BoardTeam
     * const boardTeam = await prisma.boardTeam.findFirstOrThrow({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findFirstOrThrow<T extends BoardTeamFindFirstOrThrowArgs>(args?: SelectSubset<T, BoardTeamFindFirstOrThrowArgs<ExtArgs>>): Prisma__BoardTeamClient<$Result.GetResult<Prisma.$BoardTeamPayload<ExtArgs>, T, "findFirstOrThrow", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Find zero or more BoardTeams that matches the filter.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {BoardTeamFindManyArgs} args - Arguments to filter and select certain fields only.
     * @example
     * // Get all BoardTeams
     * const boardTeams = await prisma.boardTeam.findMany()
     * 
     * // Get first 10 BoardTeams
     * const boardTeams = await prisma.boardTeam.findMany({ take: 10 })
     * 
     * // Only select the `id`
     * const boardTeamWithIdOnly = await prisma.boardTeam.findMany({ select: { id: true } })
     * 
     */
    findMany<T extends BoardTeamFindManyArgs>(args?: SelectSubset<T, BoardTeamFindManyArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$BoardTeamPayload<ExtArgs>, T, "findMany", GlobalOmitOptions>>

    /**
     * Create a BoardTeam.
     * @param {BoardTeamCreateArgs} args - Arguments to create a BoardTeam.
     * @example
     * // Create one BoardTeam
     * const BoardTeam = await prisma.boardTeam.create({
     *   data: {
     *     // ... data to create a BoardTeam
     *   }
     * })
     * 
     */
    create<T extends BoardTeamCreateArgs>(args: SelectSubset<T, BoardTeamCreateArgs<ExtArgs>>): Prisma__BoardTeamClient<$Result.GetResult<Prisma.$BoardTeamPayload<ExtArgs>, T, "create", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Create many BoardTeams.
     * @param {BoardTeamCreateManyArgs} args - Arguments to create many BoardTeams.
     * @example
     * // Create many BoardTeams
     * const boardTeam = await prisma.boardTeam.createMany({
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     *     
     */
    createMany<T extends BoardTeamCreateManyArgs>(args?: SelectSubset<T, BoardTeamCreateManyArgs<ExtArgs>>): Prisma.PrismaPromise<BatchPayload>

    /**
     * Create many BoardTeams and returns the data saved in the database.
     * @param {BoardTeamCreateManyAndReturnArgs} args - Arguments to create many BoardTeams.
     * @example
     * // Create many BoardTeams
     * const boardTeam = await prisma.boardTeam.createManyAndReturn({
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * 
     * // Create many BoardTeams and only return the `id`
     * const boardTeamWithIdOnly = await prisma.boardTeam.createManyAndReturn({
     *   select: { id: true },
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * 
     */
    createManyAndReturn<T extends BoardTeamCreateManyAndReturnArgs>(args?: SelectSubset<T, BoardTeamCreateManyAndReturnArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$BoardTeamPayload<ExtArgs>, T, "createManyAndReturn", GlobalOmitOptions>>

    /**
     * Delete a BoardTeam.
     * @param {BoardTeamDeleteArgs} args - Arguments to delete one BoardTeam.
     * @example
     * // Delete one BoardTeam
     * const BoardTeam = await prisma.boardTeam.delete({
     *   where: {
     *     // ... filter to delete one BoardTeam
     *   }
     * })
     * 
     */
    delete<T extends BoardTeamDeleteArgs>(args: SelectSubset<T, BoardTeamDeleteArgs<ExtArgs>>): Prisma__BoardTeamClient<$Result.GetResult<Prisma.$BoardTeamPayload<ExtArgs>, T, "delete", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Update one BoardTeam.
     * @param {BoardTeamUpdateArgs} args - Arguments to update one BoardTeam.
     * @example
     * // Update one BoardTeam
     * const boardTeam = await prisma.boardTeam.update({
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: {
     *     // ... provide data here
     *   }
     * })
     * 
     */
    update<T extends BoardTeamUpdateArgs>(args: SelectSubset<T, BoardTeamUpdateArgs<ExtArgs>>): Prisma__BoardTeamClient<$Result.GetResult<Prisma.$BoardTeamPayload<ExtArgs>, T, "update", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Delete zero or more BoardTeams.
     * @param {BoardTeamDeleteManyArgs} args - Arguments to filter BoardTeams to delete.
     * @example
     * // Delete a few BoardTeams
     * const { count } = await prisma.boardTeam.deleteMany({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     * 
     */
    deleteMany<T extends BoardTeamDeleteManyArgs>(args?: SelectSubset<T, BoardTeamDeleteManyArgs<ExtArgs>>): Prisma.PrismaPromise<BatchPayload>

    /**
     * Update zero or more BoardTeams.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {BoardTeamUpdateManyArgs} args - Arguments to update one or more rows.
     * @example
     * // Update many BoardTeams
     * const boardTeam = await prisma.boardTeam.updateMany({
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: {
     *     // ... provide data here
     *   }
     * })
     * 
     */
    updateMany<T extends BoardTeamUpdateManyArgs>(args: SelectSubset<T, BoardTeamUpdateManyArgs<ExtArgs>>): Prisma.PrismaPromise<BatchPayload>

    /**
     * Update zero or more BoardTeams and returns the data updated in the database.
     * @param {BoardTeamUpdateManyAndReturnArgs} args - Arguments to update many BoardTeams.
     * @example
     * // Update many BoardTeams
     * const boardTeam = await prisma.boardTeam.updateManyAndReturn({
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * 
     * // Update zero or more BoardTeams and only return the `id`
     * const boardTeamWithIdOnly = await prisma.boardTeam.updateManyAndReturn({
     *   select: { id: true },
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * 
     */
    updateManyAndReturn<T extends BoardTeamUpdateManyAndReturnArgs>(args: SelectSubset<T, BoardTeamUpdateManyAndReturnArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$BoardTeamPayload<ExtArgs>, T, "updateManyAndReturn", GlobalOmitOptions>>

    /**
     * Create or update one BoardTeam.
     * @param {BoardTeamUpsertArgs} args - Arguments to update or create a BoardTeam.
     * @example
     * // Update or create a BoardTeam
     * const boardTeam = await prisma.boardTeam.upsert({
     *   create: {
     *     // ... data to create a BoardTeam
     *   },
     *   update: {
     *     // ... in case it already exists, update
     *   },
     *   where: {
     *     // ... the filter for the BoardTeam we want to update
     *   }
     * })
     */
    upsert<T extends BoardTeamUpsertArgs>(args: SelectSubset<T, BoardTeamUpsertArgs<ExtArgs>>): Prisma__BoardTeamClient<$Result.GetResult<Prisma.$BoardTeamPayload<ExtArgs>, T, "upsert", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>


    /**
     * Count the number of BoardTeams.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {BoardTeamCountArgs} args - Arguments to filter BoardTeams to count.
     * @example
     * // Count the number of BoardTeams
     * const count = await prisma.boardTeam.count({
     *   where: {
     *     // ... the filter for the BoardTeams we want to count
     *   }
     * })
    **/
    count<T extends BoardTeamCountArgs>(
      args?: Subset<T, BoardTeamCountArgs>,
    ): Prisma.PrismaPromise<
      T extends $Utils.Record<'select', any>
        ? T['select'] extends true
          ? number
          : GetScalarType<T['select'], BoardTeamCountAggregateOutputType>
        : number
    >

    /**
     * Allows you to perform aggregations operations on a BoardTeam.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {BoardTeamAggregateArgs} args - Select which aggregations you would like to apply and on what fields.
     * @example
     * // Ordered by age ascending
     * // Where email contains prisma.io
     * // Limited to the 10 users
     * const aggregations = await prisma.user.aggregate({
     *   _avg: {
     *     age: true,
     *   },
     *   where: {
     *     email: {
     *       contains: "prisma.io",
     *     },
     *   },
     *   orderBy: {
     *     age: "asc",
     *   },
     *   take: 10,
     * })
    **/
    aggregate<T extends BoardTeamAggregateArgs>(args: Subset<T, BoardTeamAggregateArgs>): Prisma.PrismaPromise<GetBoardTeamAggregateType<T>>

    /**
     * Group by BoardTeam.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {BoardTeamGroupByArgs} args - Group by arguments.
     * @example
     * // Group by city, order by createdAt, get count
     * const result = await prisma.user.groupBy({
     *   by: ['city', 'createdAt'],
     *   orderBy: {
     *     createdAt: true
     *   },
     *   _count: {
     *     _all: true
     *   },
     * })
     * 
    **/
    groupBy<
      T extends BoardTeamGroupByArgs,
      HasSelectOrTake extends Or<
        Extends<'skip', Keys<T>>,
        Extends<'take', Keys<T>>
      >,
      OrderByArg extends True extends HasSelectOrTake
        ? { orderBy: BoardTeamGroupByArgs['orderBy'] }
        : { orderBy?: BoardTeamGroupByArgs['orderBy'] },
      OrderFields extends ExcludeUnderscoreKeys<Keys<MaybeTupleToUnion<T['orderBy']>>>,
      ByFields extends MaybeTupleToUnion<T['by']>,
      ByValid extends Has<ByFields, OrderFields>,
      HavingFields extends GetHavingFields<T['having']>,
      HavingValid extends Has<ByFields, HavingFields>,
      ByEmpty extends T['by'] extends never[] ? True : False,
      InputErrors extends ByEmpty extends True
      ? `Error: "by" must not be empty.`
      : HavingValid extends False
      ? {
          [P in HavingFields]: P extends ByFields
            ? never
            : P extends string
            ? `Error: Field "${P}" used in "having" needs to be provided in "by".`
            : [
                Error,
                'Field ',
                P,
                ` in "having" needs to be provided in "by"`,
              ]
        }[HavingFields]
      : 'take' extends Keys<T>
      ? 'orderBy' extends Keys<T>
        ? ByValid extends True
          ? {}
          : {
              [P in OrderFields]: P extends ByFields
                ? never
                : `Error: Field "${P}" in "orderBy" needs to be provided in "by"`
            }[OrderFields]
        : 'Error: If you provide "take", you also need to provide "orderBy"'
      : 'skip' extends Keys<T>
      ? 'orderBy' extends Keys<T>
        ? ByValid extends True
          ? {}
          : {
              [P in OrderFields]: P extends ByFields
                ? never
                : `Error: Field "${P}" in "orderBy" needs to be provided in "by"`
            }[OrderFields]
        : 'Error: If you provide "skip", you also need to provide "orderBy"'
      : ByValid extends True
      ? {}
      : {
          [P in OrderFields]: P extends ByFields
            ? never
            : `Error: Field "${P}" in "orderBy" needs to be provided in "by"`
        }[OrderFields]
    >(args: SubsetIntersection<T, BoardTeamGroupByArgs, OrderByArg> & InputErrors): {} extends InputErrors ? GetBoardTeamGroupByPayload<T> : Prisma.PrismaPromise<InputErrors>
  /**
   * Fields of the BoardTeam model
   */
  readonly fields: BoardTeamFieldRefs;
  }

  /**
   * The delegate class that acts as a "Promise-like" for BoardTeam.
   * Why is this prefixed with `Prisma__`?
   * Because we want to prevent naming conflicts as mentioned in
   * https://github.com/prisma/prisma-client-js/issues/707
   */
  export interface Prisma__BoardTeamClient<T, Null = never, ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs, GlobalOmitOptions = {}> extends Prisma.PrismaPromise<T> {
    readonly [Symbol.toStringTag]: "PrismaPromise"
    board<T extends BoardDefaultArgs<ExtArgs> = {}>(args?: Subset<T, BoardDefaultArgs<ExtArgs>>): Prisma__BoardClient<$Result.GetResult<Prisma.$BoardPayload<ExtArgs>, T, "findUniqueOrThrow", GlobalOmitOptions> | Null, Null, ExtArgs, GlobalOmitOptions>
    team<T extends TeamDefaultArgs<ExtArgs> = {}>(args?: Subset<T, TeamDefaultArgs<ExtArgs>>): Prisma__TeamClient<$Result.GetResult<Prisma.$TeamPayload<ExtArgs>, T, "findUniqueOrThrow", GlobalOmitOptions> | Null, Null, ExtArgs, GlobalOmitOptions>
    /**
     * Attaches callbacks for the resolution and/or rejection of the Promise.
     * @param onfulfilled The callback to execute when the Promise is resolved.
     * @param onrejected The callback to execute when the Promise is rejected.
     * @returns A Promise for the completion of which ever callback is executed.
     */
    then<TResult1 = T, TResult2 = never>(onfulfilled?: ((value: T) => TResult1 | PromiseLike<TResult1>) | undefined | null, onrejected?: ((reason: any) => TResult2 | PromiseLike<TResult2>) | undefined | null): $Utils.JsPromise<TResult1 | TResult2>
    /**
     * Attaches a callback for only the rejection of the Promise.
     * @param onrejected The callback to execute when the Promise is rejected.
     * @returns A Promise for the completion of the callback.
     */
    catch<TResult = never>(onrejected?: ((reason: any) => TResult | PromiseLike<TResult>) | undefined | null): $Utils.JsPromise<T | TResult>
    /**
     * Attaches a callback that is invoked when the Promise is settled (fulfilled or rejected). The
     * resolved value cannot be modified from the callback.
     * @param onfinally The callback to execute when the Promise is settled (fulfilled or rejected).
     * @returns A Promise for the completion of the callback.
     */
    finally(onfinally?: (() => void) | undefined | null): $Utils.JsPromise<T>
  }




  /**
   * Fields of the BoardTeam model
   */
  interface BoardTeamFieldRefs {
    readonly id: FieldRef<"BoardTeam", 'String'>
    readonly boardId: FieldRef<"BoardTeam", 'String'>
    readonly teamId: FieldRef<"BoardTeam", 'String'>
    readonly createdAt: FieldRef<"BoardTeam", 'DateTime'>
  }
    

  // Custom InputTypes
  /**
   * BoardTeam findUnique
   */
  export type BoardTeamFindUniqueArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the BoardTeam
     */
    select?: BoardTeamSelect<ExtArgs> | null
    /**
     * Omit specific fields from the BoardTeam
     */
    omit?: BoardTeamOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: BoardTeamInclude<ExtArgs> | null
    /**
     * Filter, which BoardTeam to fetch.
     */
    where: BoardTeamWhereUniqueInput
  }

  /**
   * BoardTeam findUniqueOrThrow
   */
  export type BoardTeamFindUniqueOrThrowArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the BoardTeam
     */
    select?: BoardTeamSelect<ExtArgs> | null
    /**
     * Omit specific fields from the BoardTeam
     */
    omit?: BoardTeamOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: BoardTeamInclude<ExtArgs> | null
    /**
     * Filter, which BoardTeam to fetch.
     */
    where: BoardTeamWhereUniqueInput
  }

  /**
   * BoardTeam findFirst
   */
  export type BoardTeamFindFirstArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the BoardTeam
     */
    select?: BoardTeamSelect<ExtArgs> | null
    /**
     * Omit specific fields from the BoardTeam
     */
    omit?: BoardTeamOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: BoardTeamInclude<ExtArgs> | null
    /**
     * Filter, which BoardTeam to fetch.
     */
    where?: BoardTeamWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of BoardTeams to fetch.
     */
    orderBy?: BoardTeamOrderByWithRelationInput | BoardTeamOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the position for searching for BoardTeams.
     */
    cursor?: BoardTeamWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` BoardTeams from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` BoardTeams.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/distinct Distinct Docs}
     * 
     * Filter by unique combinations of BoardTeams.
     */
    distinct?: BoardTeamScalarFieldEnum | BoardTeamScalarFieldEnum[]
  }

  /**
   * BoardTeam findFirstOrThrow
   */
  export type BoardTeamFindFirstOrThrowArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the BoardTeam
     */
    select?: BoardTeamSelect<ExtArgs> | null
    /**
     * Omit specific fields from the BoardTeam
     */
    omit?: BoardTeamOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: BoardTeamInclude<ExtArgs> | null
    /**
     * Filter, which BoardTeam to fetch.
     */
    where?: BoardTeamWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of BoardTeams to fetch.
     */
    orderBy?: BoardTeamOrderByWithRelationInput | BoardTeamOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the position for searching for BoardTeams.
     */
    cursor?: BoardTeamWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` BoardTeams from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` BoardTeams.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/distinct Distinct Docs}
     * 
     * Filter by unique combinations of BoardTeams.
     */
    distinct?: BoardTeamScalarFieldEnum | BoardTeamScalarFieldEnum[]
  }

  /**
   * BoardTeam findMany
   */
  export type BoardTeamFindManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the BoardTeam
     */
    select?: BoardTeamSelect<ExtArgs> | null
    /**
     * Omit specific fields from the BoardTeam
     */
    omit?: BoardTeamOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: BoardTeamInclude<ExtArgs> | null
    /**
     * Filter, which BoardTeams to fetch.
     */
    where?: BoardTeamWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of BoardTeams to fetch.
     */
    orderBy?: BoardTeamOrderByWithRelationInput | BoardTeamOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the position for listing BoardTeams.
     */
    cursor?: BoardTeamWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` BoardTeams from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` BoardTeams.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/distinct Distinct Docs}
     * 
     * Filter by unique combinations of BoardTeams.
     */
    distinct?: BoardTeamScalarFieldEnum | BoardTeamScalarFieldEnum[]
  }

  /**
   * BoardTeam create
   */
  export type BoardTeamCreateArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the BoardTeam
     */
    select?: BoardTeamSelect<ExtArgs> | null
    /**
     * Omit specific fields from the BoardTeam
     */
    omit?: BoardTeamOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: BoardTeamInclude<ExtArgs> | null
    /**
     * The data needed to create a BoardTeam.
     */
    data: XOR<BoardTeamCreateInput, BoardTeamUncheckedCreateInput>
  }

  /**
   * BoardTeam createMany
   */
  export type BoardTeamCreateManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * The data used to create many BoardTeams.
     */
    data: BoardTeamCreateManyInput | BoardTeamCreateManyInput[]
    skipDuplicates?: boolean
  }

  /**
   * BoardTeam createManyAndReturn
   */
  export type BoardTeamCreateManyAndReturnArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the BoardTeam
     */
    select?: BoardTeamSelectCreateManyAndReturn<ExtArgs> | null
    /**
     * Omit specific fields from the BoardTeam
     */
    omit?: BoardTeamOmit<ExtArgs> | null
    /**
     * The data used to create many BoardTeams.
     */
    data: BoardTeamCreateManyInput | BoardTeamCreateManyInput[]
    skipDuplicates?: boolean
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: BoardTeamIncludeCreateManyAndReturn<ExtArgs> | null
  }

  /**
   * BoardTeam update
   */
  export type BoardTeamUpdateArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the BoardTeam
     */
    select?: BoardTeamSelect<ExtArgs> | null
    /**
     * Omit specific fields from the BoardTeam
     */
    omit?: BoardTeamOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: BoardTeamInclude<ExtArgs> | null
    /**
     * The data needed to update a BoardTeam.
     */
    data: XOR<BoardTeamUpdateInput, BoardTeamUncheckedUpdateInput>
    /**
     * Choose, which BoardTeam to update.
     */
    where: BoardTeamWhereUniqueInput
  }

  /**
   * BoardTeam updateMany
   */
  export type BoardTeamUpdateManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * The data used to update BoardTeams.
     */
    data: XOR<BoardTeamUpdateManyMutationInput, BoardTeamUncheckedUpdateManyInput>
    /**
     * Filter which BoardTeams to update
     */
    where?: BoardTeamWhereInput
    /**
     * Limit how many BoardTeams to update.
     */
    limit?: number
  }

  /**
   * BoardTeam updateManyAndReturn
   */
  export type BoardTeamUpdateManyAndReturnArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the BoardTeam
     */
    select?: BoardTeamSelectUpdateManyAndReturn<ExtArgs> | null
    /**
     * Omit specific fields from the BoardTeam
     */
    omit?: BoardTeamOmit<ExtArgs> | null
    /**
     * The data used to update BoardTeams.
     */
    data: XOR<BoardTeamUpdateManyMutationInput, BoardTeamUncheckedUpdateManyInput>
    /**
     * Filter which BoardTeams to update
     */
    where?: BoardTeamWhereInput
    /**
     * Limit how many BoardTeams to update.
     */
    limit?: number
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: BoardTeamIncludeUpdateManyAndReturn<ExtArgs> | null
  }

  /**
   * BoardTeam upsert
   */
  export type BoardTeamUpsertArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the BoardTeam
     */
    select?: BoardTeamSelect<ExtArgs> | null
    /**
     * Omit specific fields from the BoardTeam
     */
    omit?: BoardTeamOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: BoardTeamInclude<ExtArgs> | null
    /**
     * The filter to search for the BoardTeam to update in case it exists.
     */
    where: BoardTeamWhereUniqueInput
    /**
     * In case the BoardTeam found by the `where` argument doesn't exist, create a new BoardTeam with this data.
     */
    create: XOR<BoardTeamCreateInput, BoardTeamUncheckedCreateInput>
    /**
     * In case the BoardTeam was found with the provided `where` argument, update it with this data.
     */
    update: XOR<BoardTeamUpdateInput, BoardTeamUncheckedUpdateInput>
  }

  /**
   * BoardTeam delete
   */
  export type BoardTeamDeleteArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the BoardTeam
     */
    select?: BoardTeamSelect<ExtArgs> | null
    /**
     * Omit specific fields from the BoardTeam
     */
    omit?: BoardTeamOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: BoardTeamInclude<ExtArgs> | null
    /**
     * Filter which BoardTeam to delete.
     */
    where: BoardTeamWhereUniqueInput
  }

  /**
   * BoardTeam deleteMany
   */
  export type BoardTeamDeleteManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Filter which BoardTeams to delete
     */
    where?: BoardTeamWhereInput
    /**
     * Limit how many BoardTeams to delete.
     */
    limit?: number
  }

  /**
   * BoardTeam without action
   */
  export type BoardTeamDefaultArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the BoardTeam
     */
    select?: BoardTeamSelect<ExtArgs> | null
    /**
     * Omit specific fields from the BoardTeam
     */
    omit?: BoardTeamOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: BoardTeamInclude<ExtArgs> | null
  }


  /**
   * Model BoardAuthor
   */

  export type AggregateBoardAuthor = {
    _count: BoardAuthorCountAggregateOutputType | null
    _min: BoardAuthorMinAggregateOutputType | null
    _max: BoardAuthorMaxAggregateOutputType | null
  }

  export type BoardAuthorMinAggregateOutputType = {
    id: string | null
    boardId: string | null
    userId: string | null
    isOwner: boolean | null
    createdAt: Date | null
  }

  export type BoardAuthorMaxAggregateOutputType = {
    id: string | null
    boardId: string | null
    userId: string | null
    isOwner: boolean | null
    createdAt: Date | null
  }

  export type BoardAuthorCountAggregateOutputType = {
    id: number
    boardId: number
    userId: number
    isOwner: number
    createdAt: number
    _all: number
  }


  export type BoardAuthorMinAggregateInputType = {
    id?: true
    boardId?: true
    userId?: true
    isOwner?: true
    createdAt?: true
  }

  export type BoardAuthorMaxAggregateInputType = {
    id?: true
    boardId?: true
    userId?: true
    isOwner?: true
    createdAt?: true
  }

  export type BoardAuthorCountAggregateInputType = {
    id?: true
    boardId?: true
    userId?: true
    isOwner?: true
    createdAt?: true
    _all?: true
  }

  export type BoardAuthorAggregateArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Filter which BoardAuthor to aggregate.
     */
    where?: BoardAuthorWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of BoardAuthors to fetch.
     */
    orderBy?: BoardAuthorOrderByWithRelationInput | BoardAuthorOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the start position
     */
    cursor?: BoardAuthorWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` BoardAuthors from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` BoardAuthors.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Count returned BoardAuthors
    **/
    _count?: true | BoardAuthorCountAggregateInputType
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Select which fields to find the minimum value
    **/
    _min?: BoardAuthorMinAggregateInputType
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Select which fields to find the maximum value
    **/
    _max?: BoardAuthorMaxAggregateInputType
  }

  export type GetBoardAuthorAggregateType<T extends BoardAuthorAggregateArgs> = {
        [P in keyof T & keyof AggregateBoardAuthor]: P extends '_count' | 'count'
      ? T[P] extends true
        ? number
        : GetScalarType<T[P], AggregateBoardAuthor[P]>
      : GetScalarType<T[P], AggregateBoardAuthor[P]>
  }




  export type BoardAuthorGroupByArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    where?: BoardAuthorWhereInput
    orderBy?: BoardAuthorOrderByWithAggregationInput | BoardAuthorOrderByWithAggregationInput[]
    by: BoardAuthorScalarFieldEnum[] | BoardAuthorScalarFieldEnum
    having?: BoardAuthorScalarWhereWithAggregatesInput
    take?: number
    skip?: number
    _count?: BoardAuthorCountAggregateInputType | true
    _min?: BoardAuthorMinAggregateInputType
    _max?: BoardAuthorMaxAggregateInputType
  }

  export type BoardAuthorGroupByOutputType = {
    id: string
    boardId: string
    userId: string
    isOwner: boolean
    createdAt: Date
    _count: BoardAuthorCountAggregateOutputType | null
    _min: BoardAuthorMinAggregateOutputType | null
    _max: BoardAuthorMaxAggregateOutputType | null
  }

  type GetBoardAuthorGroupByPayload<T extends BoardAuthorGroupByArgs> = Prisma.PrismaPromise<
    Array<
      PickEnumerable<BoardAuthorGroupByOutputType, T['by']> &
        {
          [P in ((keyof T) & (keyof BoardAuthorGroupByOutputType))]: P extends '_count'
            ? T[P] extends boolean
              ? number
              : GetScalarType<T[P], BoardAuthorGroupByOutputType[P]>
            : GetScalarType<T[P], BoardAuthorGroupByOutputType[P]>
        }
      >
    >


  export type BoardAuthorSelect<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetSelect<{
    id?: boolean
    boardId?: boolean
    userId?: boolean
    isOwner?: boolean
    createdAt?: boolean
    board?: boolean | BoardDefaultArgs<ExtArgs>
    user?: boolean | UserDefaultArgs<ExtArgs>
  }, ExtArgs["result"]["boardAuthor"]>

  export type BoardAuthorSelectCreateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetSelect<{
    id?: boolean
    boardId?: boolean
    userId?: boolean
    isOwner?: boolean
    createdAt?: boolean
    board?: boolean | BoardDefaultArgs<ExtArgs>
    user?: boolean | UserDefaultArgs<ExtArgs>
  }, ExtArgs["result"]["boardAuthor"]>

  export type BoardAuthorSelectUpdateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetSelect<{
    id?: boolean
    boardId?: boolean
    userId?: boolean
    isOwner?: boolean
    createdAt?: boolean
    board?: boolean | BoardDefaultArgs<ExtArgs>
    user?: boolean | UserDefaultArgs<ExtArgs>
  }, ExtArgs["result"]["boardAuthor"]>

  export type BoardAuthorSelectScalar = {
    id?: boolean
    boardId?: boolean
    userId?: boolean
    isOwner?: boolean
    createdAt?: boolean
  }

  export type BoardAuthorOmit<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetOmit<"id" | "boardId" | "userId" | "isOwner" | "createdAt", ExtArgs["result"]["boardAuthor"]>
  export type BoardAuthorInclude<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    board?: boolean | BoardDefaultArgs<ExtArgs>
    user?: boolean | UserDefaultArgs<ExtArgs>
  }
  export type BoardAuthorIncludeCreateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    board?: boolean | BoardDefaultArgs<ExtArgs>
    user?: boolean | UserDefaultArgs<ExtArgs>
  }
  export type BoardAuthorIncludeUpdateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    board?: boolean | BoardDefaultArgs<ExtArgs>
    user?: boolean | UserDefaultArgs<ExtArgs>
  }

  export type $BoardAuthorPayload<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    name: "BoardAuthor"
    objects: {
      board: Prisma.$BoardPayload<ExtArgs>
      user: Prisma.$UserPayload<ExtArgs>
    }
    scalars: $Extensions.GetPayloadResult<{
      id: string
      boardId: string
      userId: string
      isOwner: boolean
      createdAt: Date
    }, ExtArgs["result"]["boardAuthor"]>
    composites: {}
  }

  type BoardAuthorGetPayload<S extends boolean | null | undefined | BoardAuthorDefaultArgs> = $Result.GetResult<Prisma.$BoardAuthorPayload, S>

  type BoardAuthorCountArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> =
    Omit<BoardAuthorFindManyArgs, 'select' | 'include' | 'distinct' | 'omit'> & {
      select?: BoardAuthorCountAggregateInputType | true
    }

  export interface BoardAuthorDelegate<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs, GlobalOmitOptions = {}> {
    [K: symbol]: { types: Prisma.TypeMap<ExtArgs>['model']['BoardAuthor'], meta: { name: 'BoardAuthor' } }
    /**
     * Find zero or one BoardAuthor that matches the filter.
     * @param {BoardAuthorFindUniqueArgs} args - Arguments to find a BoardAuthor
     * @example
     * // Get one BoardAuthor
     * const boardAuthor = await prisma.boardAuthor.findUnique({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findUnique<T extends BoardAuthorFindUniqueArgs>(args: SelectSubset<T, BoardAuthorFindUniqueArgs<ExtArgs>>): Prisma__BoardAuthorClient<$Result.GetResult<Prisma.$BoardAuthorPayload<ExtArgs>, T, "findUnique", GlobalOmitOptions> | null, null, ExtArgs, GlobalOmitOptions>

    /**
     * Find one BoardAuthor that matches the filter or throw an error with `error.code='P2025'`
     * if no matches were found.
     * @param {BoardAuthorFindUniqueOrThrowArgs} args - Arguments to find a BoardAuthor
     * @example
     * // Get one BoardAuthor
     * const boardAuthor = await prisma.boardAuthor.findUniqueOrThrow({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findUniqueOrThrow<T extends BoardAuthorFindUniqueOrThrowArgs>(args: SelectSubset<T, BoardAuthorFindUniqueOrThrowArgs<ExtArgs>>): Prisma__BoardAuthorClient<$Result.GetResult<Prisma.$BoardAuthorPayload<ExtArgs>, T, "findUniqueOrThrow", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Find the first BoardAuthor that matches the filter.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {BoardAuthorFindFirstArgs} args - Arguments to find a BoardAuthor
     * @example
     * // Get one BoardAuthor
     * const boardAuthor = await prisma.boardAuthor.findFirst({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findFirst<T extends BoardAuthorFindFirstArgs>(args?: SelectSubset<T, BoardAuthorFindFirstArgs<ExtArgs>>): Prisma__BoardAuthorClient<$Result.GetResult<Prisma.$BoardAuthorPayload<ExtArgs>, T, "findFirst", GlobalOmitOptions> | null, null, ExtArgs, GlobalOmitOptions>

    /**
     * Find the first BoardAuthor that matches the filter or
     * throw `PrismaKnownClientError` with `P2025` code if no matches were found.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {BoardAuthorFindFirstOrThrowArgs} args - Arguments to find a BoardAuthor
     * @example
     * // Get one BoardAuthor
     * const boardAuthor = await prisma.boardAuthor.findFirstOrThrow({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findFirstOrThrow<T extends BoardAuthorFindFirstOrThrowArgs>(args?: SelectSubset<T, BoardAuthorFindFirstOrThrowArgs<ExtArgs>>): Prisma__BoardAuthorClient<$Result.GetResult<Prisma.$BoardAuthorPayload<ExtArgs>, T, "findFirstOrThrow", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Find zero or more BoardAuthors that matches the filter.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {BoardAuthorFindManyArgs} args - Arguments to filter and select certain fields only.
     * @example
     * // Get all BoardAuthors
     * const boardAuthors = await prisma.boardAuthor.findMany()
     * 
     * // Get first 10 BoardAuthors
     * const boardAuthors = await prisma.boardAuthor.findMany({ take: 10 })
     * 
     * // Only select the `id`
     * const boardAuthorWithIdOnly = await prisma.boardAuthor.findMany({ select: { id: true } })
     * 
     */
    findMany<T extends BoardAuthorFindManyArgs>(args?: SelectSubset<T, BoardAuthorFindManyArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$BoardAuthorPayload<ExtArgs>, T, "findMany", GlobalOmitOptions>>

    /**
     * Create a BoardAuthor.
     * @param {BoardAuthorCreateArgs} args - Arguments to create a BoardAuthor.
     * @example
     * // Create one BoardAuthor
     * const BoardAuthor = await prisma.boardAuthor.create({
     *   data: {
     *     // ... data to create a BoardAuthor
     *   }
     * })
     * 
     */
    create<T extends BoardAuthorCreateArgs>(args: SelectSubset<T, BoardAuthorCreateArgs<ExtArgs>>): Prisma__BoardAuthorClient<$Result.GetResult<Prisma.$BoardAuthorPayload<ExtArgs>, T, "create", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Create many BoardAuthors.
     * @param {BoardAuthorCreateManyArgs} args - Arguments to create many BoardAuthors.
     * @example
     * // Create many BoardAuthors
     * const boardAuthor = await prisma.boardAuthor.createMany({
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     *     
     */
    createMany<T extends BoardAuthorCreateManyArgs>(args?: SelectSubset<T, BoardAuthorCreateManyArgs<ExtArgs>>): Prisma.PrismaPromise<BatchPayload>

    /**
     * Create many BoardAuthors and returns the data saved in the database.
     * @param {BoardAuthorCreateManyAndReturnArgs} args - Arguments to create many BoardAuthors.
     * @example
     * // Create many BoardAuthors
     * const boardAuthor = await prisma.boardAuthor.createManyAndReturn({
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * 
     * // Create many BoardAuthors and only return the `id`
     * const boardAuthorWithIdOnly = await prisma.boardAuthor.createManyAndReturn({
     *   select: { id: true },
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * 
     */
    createManyAndReturn<T extends BoardAuthorCreateManyAndReturnArgs>(args?: SelectSubset<T, BoardAuthorCreateManyAndReturnArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$BoardAuthorPayload<ExtArgs>, T, "createManyAndReturn", GlobalOmitOptions>>

    /**
     * Delete a BoardAuthor.
     * @param {BoardAuthorDeleteArgs} args - Arguments to delete one BoardAuthor.
     * @example
     * // Delete one BoardAuthor
     * const BoardAuthor = await prisma.boardAuthor.delete({
     *   where: {
     *     // ... filter to delete one BoardAuthor
     *   }
     * })
     * 
     */
    delete<T extends BoardAuthorDeleteArgs>(args: SelectSubset<T, BoardAuthorDeleteArgs<ExtArgs>>): Prisma__BoardAuthorClient<$Result.GetResult<Prisma.$BoardAuthorPayload<ExtArgs>, T, "delete", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Update one BoardAuthor.
     * @param {BoardAuthorUpdateArgs} args - Arguments to update one BoardAuthor.
     * @example
     * // Update one BoardAuthor
     * const boardAuthor = await prisma.boardAuthor.update({
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: {
     *     // ... provide data here
     *   }
     * })
     * 
     */
    update<T extends BoardAuthorUpdateArgs>(args: SelectSubset<T, BoardAuthorUpdateArgs<ExtArgs>>): Prisma__BoardAuthorClient<$Result.GetResult<Prisma.$BoardAuthorPayload<ExtArgs>, T, "update", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Delete zero or more BoardAuthors.
     * @param {BoardAuthorDeleteManyArgs} args - Arguments to filter BoardAuthors to delete.
     * @example
     * // Delete a few BoardAuthors
     * const { count } = await prisma.boardAuthor.deleteMany({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     * 
     */
    deleteMany<T extends BoardAuthorDeleteManyArgs>(args?: SelectSubset<T, BoardAuthorDeleteManyArgs<ExtArgs>>): Prisma.PrismaPromise<BatchPayload>

    /**
     * Update zero or more BoardAuthors.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {BoardAuthorUpdateManyArgs} args - Arguments to update one or more rows.
     * @example
     * // Update many BoardAuthors
     * const boardAuthor = await prisma.boardAuthor.updateMany({
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: {
     *     // ... provide data here
     *   }
     * })
     * 
     */
    updateMany<T extends BoardAuthorUpdateManyArgs>(args: SelectSubset<T, BoardAuthorUpdateManyArgs<ExtArgs>>): Prisma.PrismaPromise<BatchPayload>

    /**
     * Update zero or more BoardAuthors and returns the data updated in the database.
     * @param {BoardAuthorUpdateManyAndReturnArgs} args - Arguments to update many BoardAuthors.
     * @example
     * // Update many BoardAuthors
     * const boardAuthor = await prisma.boardAuthor.updateManyAndReturn({
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * 
     * // Update zero or more BoardAuthors and only return the `id`
     * const boardAuthorWithIdOnly = await prisma.boardAuthor.updateManyAndReturn({
     *   select: { id: true },
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * 
     */
    updateManyAndReturn<T extends BoardAuthorUpdateManyAndReturnArgs>(args: SelectSubset<T, BoardAuthorUpdateManyAndReturnArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$BoardAuthorPayload<ExtArgs>, T, "updateManyAndReturn", GlobalOmitOptions>>

    /**
     * Create or update one BoardAuthor.
     * @param {BoardAuthorUpsertArgs} args - Arguments to update or create a BoardAuthor.
     * @example
     * // Update or create a BoardAuthor
     * const boardAuthor = await prisma.boardAuthor.upsert({
     *   create: {
     *     // ... data to create a BoardAuthor
     *   },
     *   update: {
     *     // ... in case it already exists, update
     *   },
     *   where: {
     *     // ... the filter for the BoardAuthor we want to update
     *   }
     * })
     */
    upsert<T extends BoardAuthorUpsertArgs>(args: SelectSubset<T, BoardAuthorUpsertArgs<ExtArgs>>): Prisma__BoardAuthorClient<$Result.GetResult<Prisma.$BoardAuthorPayload<ExtArgs>, T, "upsert", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>


    /**
     * Count the number of BoardAuthors.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {BoardAuthorCountArgs} args - Arguments to filter BoardAuthors to count.
     * @example
     * // Count the number of BoardAuthors
     * const count = await prisma.boardAuthor.count({
     *   where: {
     *     // ... the filter for the BoardAuthors we want to count
     *   }
     * })
    **/
    count<T extends BoardAuthorCountArgs>(
      args?: Subset<T, BoardAuthorCountArgs>,
    ): Prisma.PrismaPromise<
      T extends $Utils.Record<'select', any>
        ? T['select'] extends true
          ? number
          : GetScalarType<T['select'], BoardAuthorCountAggregateOutputType>
        : number
    >

    /**
     * Allows you to perform aggregations operations on a BoardAuthor.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {BoardAuthorAggregateArgs} args - Select which aggregations you would like to apply and on what fields.
     * @example
     * // Ordered by age ascending
     * // Where email contains prisma.io
     * // Limited to the 10 users
     * const aggregations = await prisma.user.aggregate({
     *   _avg: {
     *     age: true,
     *   },
     *   where: {
     *     email: {
     *       contains: "prisma.io",
     *     },
     *   },
     *   orderBy: {
     *     age: "asc",
     *   },
     *   take: 10,
     * })
    **/
    aggregate<T extends BoardAuthorAggregateArgs>(args: Subset<T, BoardAuthorAggregateArgs>): Prisma.PrismaPromise<GetBoardAuthorAggregateType<T>>

    /**
     * Group by BoardAuthor.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {BoardAuthorGroupByArgs} args - Group by arguments.
     * @example
     * // Group by city, order by createdAt, get count
     * const result = await prisma.user.groupBy({
     *   by: ['city', 'createdAt'],
     *   orderBy: {
     *     createdAt: true
     *   },
     *   _count: {
     *     _all: true
     *   },
     * })
     * 
    **/
    groupBy<
      T extends BoardAuthorGroupByArgs,
      HasSelectOrTake extends Or<
        Extends<'skip', Keys<T>>,
        Extends<'take', Keys<T>>
      >,
      OrderByArg extends True extends HasSelectOrTake
        ? { orderBy: BoardAuthorGroupByArgs['orderBy'] }
        : { orderBy?: BoardAuthorGroupByArgs['orderBy'] },
      OrderFields extends ExcludeUnderscoreKeys<Keys<MaybeTupleToUnion<T['orderBy']>>>,
      ByFields extends MaybeTupleToUnion<T['by']>,
      ByValid extends Has<ByFields, OrderFields>,
      HavingFields extends GetHavingFields<T['having']>,
      HavingValid extends Has<ByFields, HavingFields>,
      ByEmpty extends T['by'] extends never[] ? True : False,
      InputErrors extends ByEmpty extends True
      ? `Error: "by" must not be empty.`
      : HavingValid extends False
      ? {
          [P in HavingFields]: P extends ByFields
            ? never
            : P extends string
            ? `Error: Field "${P}" used in "having" needs to be provided in "by".`
            : [
                Error,
                'Field ',
                P,
                ` in "having" needs to be provided in "by"`,
              ]
        }[HavingFields]
      : 'take' extends Keys<T>
      ? 'orderBy' extends Keys<T>
        ? ByValid extends True
          ? {}
          : {
              [P in OrderFields]: P extends ByFields
                ? never
                : `Error: Field "${P}" in "orderBy" needs to be provided in "by"`
            }[OrderFields]
        : 'Error: If you provide "take", you also need to provide "orderBy"'
      : 'skip' extends Keys<T>
      ? 'orderBy' extends Keys<T>
        ? ByValid extends True
          ? {}
          : {
              [P in OrderFields]: P extends ByFields
                ? never
                : `Error: Field "${P}" in "orderBy" needs to be provided in "by"`
            }[OrderFields]
        : 'Error: If you provide "skip", you also need to provide "orderBy"'
      : ByValid extends True
      ? {}
      : {
          [P in OrderFields]: P extends ByFields
            ? never
            : `Error: Field "${P}" in "orderBy" needs to be provided in "by"`
        }[OrderFields]
    >(args: SubsetIntersection<T, BoardAuthorGroupByArgs, OrderByArg> & InputErrors): {} extends InputErrors ? GetBoardAuthorGroupByPayload<T> : Prisma.PrismaPromise<InputErrors>
  /**
   * Fields of the BoardAuthor model
   */
  readonly fields: BoardAuthorFieldRefs;
  }

  /**
   * The delegate class that acts as a "Promise-like" for BoardAuthor.
   * Why is this prefixed with `Prisma__`?
   * Because we want to prevent naming conflicts as mentioned in
   * https://github.com/prisma/prisma-client-js/issues/707
   */
  export interface Prisma__BoardAuthorClient<T, Null = never, ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs, GlobalOmitOptions = {}> extends Prisma.PrismaPromise<T> {
    readonly [Symbol.toStringTag]: "PrismaPromise"
    board<T extends BoardDefaultArgs<ExtArgs> = {}>(args?: Subset<T, BoardDefaultArgs<ExtArgs>>): Prisma__BoardClient<$Result.GetResult<Prisma.$BoardPayload<ExtArgs>, T, "findUniqueOrThrow", GlobalOmitOptions> | Null, Null, ExtArgs, GlobalOmitOptions>
    user<T extends UserDefaultArgs<ExtArgs> = {}>(args?: Subset<T, UserDefaultArgs<ExtArgs>>): Prisma__UserClient<$Result.GetResult<Prisma.$UserPayload<ExtArgs>, T, "findUniqueOrThrow", GlobalOmitOptions> | Null, Null, ExtArgs, GlobalOmitOptions>
    /**
     * Attaches callbacks for the resolution and/or rejection of the Promise.
     * @param onfulfilled The callback to execute when the Promise is resolved.
     * @param onrejected The callback to execute when the Promise is rejected.
     * @returns A Promise for the completion of which ever callback is executed.
     */
    then<TResult1 = T, TResult2 = never>(onfulfilled?: ((value: T) => TResult1 | PromiseLike<TResult1>) | undefined | null, onrejected?: ((reason: any) => TResult2 | PromiseLike<TResult2>) | undefined | null): $Utils.JsPromise<TResult1 | TResult2>
    /**
     * Attaches a callback for only the rejection of the Promise.
     * @param onrejected The callback to execute when the Promise is rejected.
     * @returns A Promise for the completion of the callback.
     */
    catch<TResult = never>(onrejected?: ((reason: any) => TResult | PromiseLike<TResult>) | undefined | null): $Utils.JsPromise<T | TResult>
    /**
     * Attaches a callback that is invoked when the Promise is settled (fulfilled or rejected). The
     * resolved value cannot be modified from the callback.
     * @param onfinally The callback to execute when the Promise is settled (fulfilled or rejected).
     * @returns A Promise for the completion of the callback.
     */
    finally(onfinally?: (() => void) | undefined | null): $Utils.JsPromise<T>
  }




  /**
   * Fields of the BoardAuthor model
   */
  interface BoardAuthorFieldRefs {
    readonly id: FieldRef<"BoardAuthor", 'String'>
    readonly boardId: FieldRef<"BoardAuthor", 'String'>
    readonly userId: FieldRef<"BoardAuthor", 'String'>
    readonly isOwner: FieldRef<"BoardAuthor", 'Boolean'>
    readonly createdAt: FieldRef<"BoardAuthor", 'DateTime'>
  }
    

  // Custom InputTypes
  /**
   * BoardAuthor findUnique
   */
  export type BoardAuthorFindUniqueArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the BoardAuthor
     */
    select?: BoardAuthorSelect<ExtArgs> | null
    /**
     * Omit specific fields from the BoardAuthor
     */
    omit?: BoardAuthorOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: BoardAuthorInclude<ExtArgs> | null
    /**
     * Filter, which BoardAuthor to fetch.
     */
    where: BoardAuthorWhereUniqueInput
  }

  /**
   * BoardAuthor findUniqueOrThrow
   */
  export type BoardAuthorFindUniqueOrThrowArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the BoardAuthor
     */
    select?: BoardAuthorSelect<ExtArgs> | null
    /**
     * Omit specific fields from the BoardAuthor
     */
    omit?: BoardAuthorOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: BoardAuthorInclude<ExtArgs> | null
    /**
     * Filter, which BoardAuthor to fetch.
     */
    where: BoardAuthorWhereUniqueInput
  }

  /**
   * BoardAuthor findFirst
   */
  export type BoardAuthorFindFirstArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the BoardAuthor
     */
    select?: BoardAuthorSelect<ExtArgs> | null
    /**
     * Omit specific fields from the BoardAuthor
     */
    omit?: BoardAuthorOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: BoardAuthorInclude<ExtArgs> | null
    /**
     * Filter, which BoardAuthor to fetch.
     */
    where?: BoardAuthorWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of BoardAuthors to fetch.
     */
    orderBy?: BoardAuthorOrderByWithRelationInput | BoardAuthorOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the position for searching for BoardAuthors.
     */
    cursor?: BoardAuthorWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` BoardAuthors from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` BoardAuthors.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/distinct Distinct Docs}
     * 
     * Filter by unique combinations of BoardAuthors.
     */
    distinct?: BoardAuthorScalarFieldEnum | BoardAuthorScalarFieldEnum[]
  }

  /**
   * BoardAuthor findFirstOrThrow
   */
  export type BoardAuthorFindFirstOrThrowArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the BoardAuthor
     */
    select?: BoardAuthorSelect<ExtArgs> | null
    /**
     * Omit specific fields from the BoardAuthor
     */
    omit?: BoardAuthorOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: BoardAuthorInclude<ExtArgs> | null
    /**
     * Filter, which BoardAuthor to fetch.
     */
    where?: BoardAuthorWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of BoardAuthors to fetch.
     */
    orderBy?: BoardAuthorOrderByWithRelationInput | BoardAuthorOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the position for searching for BoardAuthors.
     */
    cursor?: BoardAuthorWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` BoardAuthors from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` BoardAuthors.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/distinct Distinct Docs}
     * 
     * Filter by unique combinations of BoardAuthors.
     */
    distinct?: BoardAuthorScalarFieldEnum | BoardAuthorScalarFieldEnum[]
  }

  /**
   * BoardAuthor findMany
   */
  export type BoardAuthorFindManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the BoardAuthor
     */
    select?: BoardAuthorSelect<ExtArgs> | null
    /**
     * Omit specific fields from the BoardAuthor
     */
    omit?: BoardAuthorOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: BoardAuthorInclude<ExtArgs> | null
    /**
     * Filter, which BoardAuthors to fetch.
     */
    where?: BoardAuthorWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of BoardAuthors to fetch.
     */
    orderBy?: BoardAuthorOrderByWithRelationInput | BoardAuthorOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the position for listing BoardAuthors.
     */
    cursor?: BoardAuthorWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` BoardAuthors from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` BoardAuthors.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/distinct Distinct Docs}
     * 
     * Filter by unique combinations of BoardAuthors.
     */
    distinct?: BoardAuthorScalarFieldEnum | BoardAuthorScalarFieldEnum[]
  }

  /**
   * BoardAuthor create
   */
  export type BoardAuthorCreateArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the BoardAuthor
     */
    select?: BoardAuthorSelect<ExtArgs> | null
    /**
     * Omit specific fields from the BoardAuthor
     */
    omit?: BoardAuthorOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: BoardAuthorInclude<ExtArgs> | null
    /**
     * The data needed to create a BoardAuthor.
     */
    data: XOR<BoardAuthorCreateInput, BoardAuthorUncheckedCreateInput>
  }

  /**
   * BoardAuthor createMany
   */
  export type BoardAuthorCreateManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * The data used to create many BoardAuthors.
     */
    data: BoardAuthorCreateManyInput | BoardAuthorCreateManyInput[]
    skipDuplicates?: boolean
  }

  /**
   * BoardAuthor createManyAndReturn
   */
  export type BoardAuthorCreateManyAndReturnArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the BoardAuthor
     */
    select?: BoardAuthorSelectCreateManyAndReturn<ExtArgs> | null
    /**
     * Omit specific fields from the BoardAuthor
     */
    omit?: BoardAuthorOmit<ExtArgs> | null
    /**
     * The data used to create many BoardAuthors.
     */
    data: BoardAuthorCreateManyInput | BoardAuthorCreateManyInput[]
    skipDuplicates?: boolean
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: BoardAuthorIncludeCreateManyAndReturn<ExtArgs> | null
  }

  /**
   * BoardAuthor update
   */
  export type BoardAuthorUpdateArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the BoardAuthor
     */
    select?: BoardAuthorSelect<ExtArgs> | null
    /**
     * Omit specific fields from the BoardAuthor
     */
    omit?: BoardAuthorOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: BoardAuthorInclude<ExtArgs> | null
    /**
     * The data needed to update a BoardAuthor.
     */
    data: XOR<BoardAuthorUpdateInput, BoardAuthorUncheckedUpdateInput>
    /**
     * Choose, which BoardAuthor to update.
     */
    where: BoardAuthorWhereUniqueInput
  }

  /**
   * BoardAuthor updateMany
   */
  export type BoardAuthorUpdateManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * The data used to update BoardAuthors.
     */
    data: XOR<BoardAuthorUpdateManyMutationInput, BoardAuthorUncheckedUpdateManyInput>
    /**
     * Filter which BoardAuthors to update
     */
    where?: BoardAuthorWhereInput
    /**
     * Limit how many BoardAuthors to update.
     */
    limit?: number
  }

  /**
   * BoardAuthor updateManyAndReturn
   */
  export type BoardAuthorUpdateManyAndReturnArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the BoardAuthor
     */
    select?: BoardAuthorSelectUpdateManyAndReturn<ExtArgs> | null
    /**
     * Omit specific fields from the BoardAuthor
     */
    omit?: BoardAuthorOmit<ExtArgs> | null
    /**
     * The data used to update BoardAuthors.
     */
    data: XOR<BoardAuthorUpdateManyMutationInput, BoardAuthorUncheckedUpdateManyInput>
    /**
     * Filter which BoardAuthors to update
     */
    where?: BoardAuthorWhereInput
    /**
     * Limit how many BoardAuthors to update.
     */
    limit?: number
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: BoardAuthorIncludeUpdateManyAndReturn<ExtArgs> | null
  }

  /**
   * BoardAuthor upsert
   */
  export type BoardAuthorUpsertArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the BoardAuthor
     */
    select?: BoardAuthorSelect<ExtArgs> | null
    /**
     * Omit specific fields from the BoardAuthor
     */
    omit?: BoardAuthorOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: BoardAuthorInclude<ExtArgs> | null
    /**
     * The filter to search for the BoardAuthor to update in case it exists.
     */
    where: BoardAuthorWhereUniqueInput
    /**
     * In case the BoardAuthor found by the `where` argument doesn't exist, create a new BoardAuthor with this data.
     */
    create: XOR<BoardAuthorCreateInput, BoardAuthorUncheckedCreateInput>
    /**
     * In case the BoardAuthor was found with the provided `where` argument, update it with this data.
     */
    update: XOR<BoardAuthorUpdateInput, BoardAuthorUncheckedUpdateInput>
  }

  /**
   * BoardAuthor delete
   */
  export type BoardAuthorDeleteArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the BoardAuthor
     */
    select?: BoardAuthorSelect<ExtArgs> | null
    /**
     * Omit specific fields from the BoardAuthor
     */
    omit?: BoardAuthorOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: BoardAuthorInclude<ExtArgs> | null
    /**
     * Filter which BoardAuthor to delete.
     */
    where: BoardAuthorWhereUniqueInput
  }

  /**
   * BoardAuthor deleteMany
   */
  export type BoardAuthorDeleteManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Filter which BoardAuthors to delete
     */
    where?: BoardAuthorWhereInput
    /**
     * Limit how many BoardAuthors to delete.
     */
    limit?: number
  }

  /**
   * BoardAuthor without action
   */
  export type BoardAuthorDefaultArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the BoardAuthor
     */
    select?: BoardAuthorSelect<ExtArgs> | null
    /**
     * Omit specific fields from the BoardAuthor
     */
    omit?: BoardAuthorOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: BoardAuthorInclude<ExtArgs> | null
  }


  /**
   * Model Tile
   */

  export type AggregateTile = {
    _count: TileCountAggregateOutputType | null
    _avg: TileAvgAggregateOutputType | null
    _sum: TileSumAggregateOutputType | null
    _min: TileMinAggregateOutputType | null
    _max: TileMaxAggregateOutputType | null
  }

  export type TileAvgAggregateOutputType = {
    position: number | null
    targetPosition: number | null
  }

  export type TileSumAggregateOutputType = {
    position: number | null
    targetPosition: number | null
  }

  export type TileMinAggregateOutputType = {
    id: string | null
    boardId: string | null
    position: number | null
    taskId: string | null
    titleOverride: string | null
    type: $Enums.TileType | null
    targetPosition: number | null
    createdAt: Date | null
    updatedAt: Date | null
  }

  export type TileMaxAggregateOutputType = {
    id: string | null
    boardId: string | null
    position: number | null
    taskId: string | null
    titleOverride: string | null
    type: $Enums.TileType | null
    targetPosition: number | null
    createdAt: Date | null
    updatedAt: Date | null
  }

  export type TileCountAggregateOutputType = {
    id: number
    boardId: number
    position: number
    taskId: number
    titleOverride: number
    type: number
    targetPosition: number
    createdAt: number
    updatedAt: number
    _all: number
  }


  export type TileAvgAggregateInputType = {
    position?: true
    targetPosition?: true
  }

  export type TileSumAggregateInputType = {
    position?: true
    targetPosition?: true
  }

  export type TileMinAggregateInputType = {
    id?: true
    boardId?: true
    position?: true
    taskId?: true
    titleOverride?: true
    type?: true
    targetPosition?: true
    createdAt?: true
    updatedAt?: true
  }

  export type TileMaxAggregateInputType = {
    id?: true
    boardId?: true
    position?: true
    taskId?: true
    titleOverride?: true
    type?: true
    targetPosition?: true
    createdAt?: true
    updatedAt?: true
  }

  export type TileCountAggregateInputType = {
    id?: true
    boardId?: true
    position?: true
    taskId?: true
    titleOverride?: true
    type?: true
    targetPosition?: true
    createdAt?: true
    updatedAt?: true
    _all?: true
  }

  export type TileAggregateArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Filter which Tile to aggregate.
     */
    where?: TileWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of Tiles to fetch.
     */
    orderBy?: TileOrderByWithRelationInput | TileOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the start position
     */
    cursor?: TileWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` Tiles from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` Tiles.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Count returned Tiles
    **/
    _count?: true | TileCountAggregateInputType
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Select which fields to average
    **/
    _avg?: TileAvgAggregateInputType
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Select which fields to sum
    **/
    _sum?: TileSumAggregateInputType
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Select which fields to find the minimum value
    **/
    _min?: TileMinAggregateInputType
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Select which fields to find the maximum value
    **/
    _max?: TileMaxAggregateInputType
  }

  export type GetTileAggregateType<T extends TileAggregateArgs> = {
        [P in keyof T & keyof AggregateTile]: P extends '_count' | 'count'
      ? T[P] extends true
        ? number
        : GetScalarType<T[P], AggregateTile[P]>
      : GetScalarType<T[P], AggregateTile[P]>
  }




  export type TileGroupByArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    where?: TileWhereInput
    orderBy?: TileOrderByWithAggregationInput | TileOrderByWithAggregationInput[]
    by: TileScalarFieldEnum[] | TileScalarFieldEnum
    having?: TileScalarWhereWithAggregatesInput
    take?: number
    skip?: number
    _count?: TileCountAggregateInputType | true
    _avg?: TileAvgAggregateInputType
    _sum?: TileSumAggregateInputType
    _min?: TileMinAggregateInputType
    _max?: TileMaxAggregateInputType
  }

  export type TileGroupByOutputType = {
    id: string
    boardId: string
    position: number
    taskId: string | null
    titleOverride: string | null
    type: $Enums.TileType
    targetPosition: number | null
    createdAt: Date
    updatedAt: Date
    _count: TileCountAggregateOutputType | null
    _avg: TileAvgAggregateOutputType | null
    _sum: TileSumAggregateOutputType | null
    _min: TileMinAggregateOutputType | null
    _max: TileMaxAggregateOutputType | null
  }

  type GetTileGroupByPayload<T extends TileGroupByArgs> = Prisma.PrismaPromise<
    Array<
      PickEnumerable<TileGroupByOutputType, T['by']> &
        {
          [P in ((keyof T) & (keyof TileGroupByOutputType))]: P extends '_count'
            ? T[P] extends boolean
              ? number
              : GetScalarType<T[P], TileGroupByOutputType[P]>
            : GetScalarType<T[P], TileGroupByOutputType[P]>
        }
      >
    >


  export type TileSelect<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetSelect<{
    id?: boolean
    boardId?: boolean
    position?: boolean
    taskId?: boolean
    titleOverride?: boolean
    type?: boolean
    targetPosition?: boolean
    createdAt?: boolean
    updatedAt?: boolean
    board?: boolean | BoardDefaultArgs<ExtArgs>
    task?: boolean | Tile$taskArgs<ExtArgs>
    completedTiles?: boolean | Tile$completedTilesArgs<ExtArgs>
    _count?: boolean | TileCountOutputTypeDefaultArgs<ExtArgs>
  }, ExtArgs["result"]["tile"]>

  export type TileSelectCreateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetSelect<{
    id?: boolean
    boardId?: boolean
    position?: boolean
    taskId?: boolean
    titleOverride?: boolean
    type?: boolean
    targetPosition?: boolean
    createdAt?: boolean
    updatedAt?: boolean
    board?: boolean | BoardDefaultArgs<ExtArgs>
    task?: boolean | Tile$taskArgs<ExtArgs>
  }, ExtArgs["result"]["tile"]>

  export type TileSelectUpdateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetSelect<{
    id?: boolean
    boardId?: boolean
    position?: boolean
    taskId?: boolean
    titleOverride?: boolean
    type?: boolean
    targetPosition?: boolean
    createdAt?: boolean
    updatedAt?: boolean
    board?: boolean | BoardDefaultArgs<ExtArgs>
    task?: boolean | Tile$taskArgs<ExtArgs>
  }, ExtArgs["result"]["tile"]>

  export type TileSelectScalar = {
    id?: boolean
    boardId?: boolean
    position?: boolean
    taskId?: boolean
    titleOverride?: boolean
    type?: boolean
    targetPosition?: boolean
    createdAt?: boolean
    updatedAt?: boolean
  }

  export type TileOmit<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetOmit<"id" | "boardId" | "position" | "taskId" | "titleOverride" | "type" | "targetPosition" | "createdAt" | "updatedAt", ExtArgs["result"]["tile"]>
  export type TileInclude<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    board?: boolean | BoardDefaultArgs<ExtArgs>
    task?: boolean | Tile$taskArgs<ExtArgs>
    completedTiles?: boolean | Tile$completedTilesArgs<ExtArgs>
    _count?: boolean | TileCountOutputTypeDefaultArgs<ExtArgs>
  }
  export type TileIncludeCreateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    board?: boolean | BoardDefaultArgs<ExtArgs>
    task?: boolean | Tile$taskArgs<ExtArgs>
  }
  export type TileIncludeUpdateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    board?: boolean | BoardDefaultArgs<ExtArgs>
    task?: boolean | Tile$taskArgs<ExtArgs>
  }

  export type $TilePayload<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    name: "Tile"
    objects: {
      board: Prisma.$BoardPayload<ExtArgs>
      task: Prisma.$TaskPayload<ExtArgs> | null
      completedTiles: Prisma.$CompletedTilePayload<ExtArgs>[]
    }
    scalars: $Extensions.GetPayloadResult<{
      id: string
      boardId: string
      position: number
      taskId: string | null
      titleOverride: string | null
      type: $Enums.TileType
      targetPosition: number | null
      createdAt: Date
      updatedAt: Date
    }, ExtArgs["result"]["tile"]>
    composites: {}
  }

  type TileGetPayload<S extends boolean | null | undefined | TileDefaultArgs> = $Result.GetResult<Prisma.$TilePayload, S>

  type TileCountArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> =
    Omit<TileFindManyArgs, 'select' | 'include' | 'distinct' | 'omit'> & {
      select?: TileCountAggregateInputType | true
    }

  export interface TileDelegate<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs, GlobalOmitOptions = {}> {
    [K: symbol]: { types: Prisma.TypeMap<ExtArgs>['model']['Tile'], meta: { name: 'Tile' } }
    /**
     * Find zero or one Tile that matches the filter.
     * @param {TileFindUniqueArgs} args - Arguments to find a Tile
     * @example
     * // Get one Tile
     * const tile = await prisma.tile.findUnique({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findUnique<T extends TileFindUniqueArgs>(args: SelectSubset<T, TileFindUniqueArgs<ExtArgs>>): Prisma__TileClient<$Result.GetResult<Prisma.$TilePayload<ExtArgs>, T, "findUnique", GlobalOmitOptions> | null, null, ExtArgs, GlobalOmitOptions>

    /**
     * Find one Tile that matches the filter or throw an error with `error.code='P2025'`
     * if no matches were found.
     * @param {TileFindUniqueOrThrowArgs} args - Arguments to find a Tile
     * @example
     * // Get one Tile
     * const tile = await prisma.tile.findUniqueOrThrow({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findUniqueOrThrow<T extends TileFindUniqueOrThrowArgs>(args: SelectSubset<T, TileFindUniqueOrThrowArgs<ExtArgs>>): Prisma__TileClient<$Result.GetResult<Prisma.$TilePayload<ExtArgs>, T, "findUniqueOrThrow", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Find the first Tile that matches the filter.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {TileFindFirstArgs} args - Arguments to find a Tile
     * @example
     * // Get one Tile
     * const tile = await prisma.tile.findFirst({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findFirst<T extends TileFindFirstArgs>(args?: SelectSubset<T, TileFindFirstArgs<ExtArgs>>): Prisma__TileClient<$Result.GetResult<Prisma.$TilePayload<ExtArgs>, T, "findFirst", GlobalOmitOptions> | null, null, ExtArgs, GlobalOmitOptions>

    /**
     * Find the first Tile that matches the filter or
     * throw `PrismaKnownClientError` with `P2025` code if no matches were found.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {TileFindFirstOrThrowArgs} args - Arguments to find a Tile
     * @example
     * // Get one Tile
     * const tile = await prisma.tile.findFirstOrThrow({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findFirstOrThrow<T extends TileFindFirstOrThrowArgs>(args?: SelectSubset<T, TileFindFirstOrThrowArgs<ExtArgs>>): Prisma__TileClient<$Result.GetResult<Prisma.$TilePayload<ExtArgs>, T, "findFirstOrThrow", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Find zero or more Tiles that matches the filter.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {TileFindManyArgs} args - Arguments to filter and select certain fields only.
     * @example
     * // Get all Tiles
     * const tiles = await prisma.tile.findMany()
     * 
     * // Get first 10 Tiles
     * const tiles = await prisma.tile.findMany({ take: 10 })
     * 
     * // Only select the `id`
     * const tileWithIdOnly = await prisma.tile.findMany({ select: { id: true } })
     * 
     */
    findMany<T extends TileFindManyArgs>(args?: SelectSubset<T, TileFindManyArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$TilePayload<ExtArgs>, T, "findMany", GlobalOmitOptions>>

    /**
     * Create a Tile.
     * @param {TileCreateArgs} args - Arguments to create a Tile.
     * @example
     * // Create one Tile
     * const Tile = await prisma.tile.create({
     *   data: {
     *     // ... data to create a Tile
     *   }
     * })
     * 
     */
    create<T extends TileCreateArgs>(args: SelectSubset<T, TileCreateArgs<ExtArgs>>): Prisma__TileClient<$Result.GetResult<Prisma.$TilePayload<ExtArgs>, T, "create", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Create many Tiles.
     * @param {TileCreateManyArgs} args - Arguments to create many Tiles.
     * @example
     * // Create many Tiles
     * const tile = await prisma.tile.createMany({
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     *     
     */
    createMany<T extends TileCreateManyArgs>(args?: SelectSubset<T, TileCreateManyArgs<ExtArgs>>): Prisma.PrismaPromise<BatchPayload>

    /**
     * Create many Tiles and returns the data saved in the database.
     * @param {TileCreateManyAndReturnArgs} args - Arguments to create many Tiles.
     * @example
     * // Create many Tiles
     * const tile = await prisma.tile.createManyAndReturn({
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * 
     * // Create many Tiles and only return the `id`
     * const tileWithIdOnly = await prisma.tile.createManyAndReturn({
     *   select: { id: true },
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * 
     */
    createManyAndReturn<T extends TileCreateManyAndReturnArgs>(args?: SelectSubset<T, TileCreateManyAndReturnArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$TilePayload<ExtArgs>, T, "createManyAndReturn", GlobalOmitOptions>>

    /**
     * Delete a Tile.
     * @param {TileDeleteArgs} args - Arguments to delete one Tile.
     * @example
     * // Delete one Tile
     * const Tile = await prisma.tile.delete({
     *   where: {
     *     // ... filter to delete one Tile
     *   }
     * })
     * 
     */
    delete<T extends TileDeleteArgs>(args: SelectSubset<T, TileDeleteArgs<ExtArgs>>): Prisma__TileClient<$Result.GetResult<Prisma.$TilePayload<ExtArgs>, T, "delete", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Update one Tile.
     * @param {TileUpdateArgs} args - Arguments to update one Tile.
     * @example
     * // Update one Tile
     * const tile = await prisma.tile.update({
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: {
     *     // ... provide data here
     *   }
     * })
     * 
     */
    update<T extends TileUpdateArgs>(args: SelectSubset<T, TileUpdateArgs<ExtArgs>>): Prisma__TileClient<$Result.GetResult<Prisma.$TilePayload<ExtArgs>, T, "update", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Delete zero or more Tiles.
     * @param {TileDeleteManyArgs} args - Arguments to filter Tiles to delete.
     * @example
     * // Delete a few Tiles
     * const { count } = await prisma.tile.deleteMany({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     * 
     */
    deleteMany<T extends TileDeleteManyArgs>(args?: SelectSubset<T, TileDeleteManyArgs<ExtArgs>>): Prisma.PrismaPromise<BatchPayload>

    /**
     * Update zero or more Tiles.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {TileUpdateManyArgs} args - Arguments to update one or more rows.
     * @example
     * // Update many Tiles
     * const tile = await prisma.tile.updateMany({
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: {
     *     // ... provide data here
     *   }
     * })
     * 
     */
    updateMany<T extends TileUpdateManyArgs>(args: SelectSubset<T, TileUpdateManyArgs<ExtArgs>>): Prisma.PrismaPromise<BatchPayload>

    /**
     * Update zero or more Tiles and returns the data updated in the database.
     * @param {TileUpdateManyAndReturnArgs} args - Arguments to update many Tiles.
     * @example
     * // Update many Tiles
     * const tile = await prisma.tile.updateManyAndReturn({
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * 
     * // Update zero or more Tiles and only return the `id`
     * const tileWithIdOnly = await prisma.tile.updateManyAndReturn({
     *   select: { id: true },
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * 
     */
    updateManyAndReturn<T extends TileUpdateManyAndReturnArgs>(args: SelectSubset<T, TileUpdateManyAndReturnArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$TilePayload<ExtArgs>, T, "updateManyAndReturn", GlobalOmitOptions>>

    /**
     * Create or update one Tile.
     * @param {TileUpsertArgs} args - Arguments to update or create a Tile.
     * @example
     * // Update or create a Tile
     * const tile = await prisma.tile.upsert({
     *   create: {
     *     // ... data to create a Tile
     *   },
     *   update: {
     *     // ... in case it already exists, update
     *   },
     *   where: {
     *     // ... the filter for the Tile we want to update
     *   }
     * })
     */
    upsert<T extends TileUpsertArgs>(args: SelectSubset<T, TileUpsertArgs<ExtArgs>>): Prisma__TileClient<$Result.GetResult<Prisma.$TilePayload<ExtArgs>, T, "upsert", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>


    /**
     * Count the number of Tiles.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {TileCountArgs} args - Arguments to filter Tiles to count.
     * @example
     * // Count the number of Tiles
     * const count = await prisma.tile.count({
     *   where: {
     *     // ... the filter for the Tiles we want to count
     *   }
     * })
    **/
    count<T extends TileCountArgs>(
      args?: Subset<T, TileCountArgs>,
    ): Prisma.PrismaPromise<
      T extends $Utils.Record<'select', any>
        ? T['select'] extends true
          ? number
          : GetScalarType<T['select'], TileCountAggregateOutputType>
        : number
    >

    /**
     * Allows you to perform aggregations operations on a Tile.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {TileAggregateArgs} args - Select which aggregations you would like to apply and on what fields.
     * @example
     * // Ordered by age ascending
     * // Where email contains prisma.io
     * // Limited to the 10 users
     * const aggregations = await prisma.user.aggregate({
     *   _avg: {
     *     age: true,
     *   },
     *   where: {
     *     email: {
     *       contains: "prisma.io",
     *     },
     *   },
     *   orderBy: {
     *     age: "asc",
     *   },
     *   take: 10,
     * })
    **/
    aggregate<T extends TileAggregateArgs>(args: Subset<T, TileAggregateArgs>): Prisma.PrismaPromise<GetTileAggregateType<T>>

    /**
     * Group by Tile.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {TileGroupByArgs} args - Group by arguments.
     * @example
     * // Group by city, order by createdAt, get count
     * const result = await prisma.user.groupBy({
     *   by: ['city', 'createdAt'],
     *   orderBy: {
     *     createdAt: true
     *   },
     *   _count: {
     *     _all: true
     *   },
     * })
     * 
    **/
    groupBy<
      T extends TileGroupByArgs,
      HasSelectOrTake extends Or<
        Extends<'skip', Keys<T>>,
        Extends<'take', Keys<T>>
      >,
      OrderByArg extends True extends HasSelectOrTake
        ? { orderBy: TileGroupByArgs['orderBy'] }
        : { orderBy?: TileGroupByArgs['orderBy'] },
      OrderFields extends ExcludeUnderscoreKeys<Keys<MaybeTupleToUnion<T['orderBy']>>>,
      ByFields extends MaybeTupleToUnion<T['by']>,
      ByValid extends Has<ByFields, OrderFields>,
      HavingFields extends GetHavingFields<T['having']>,
      HavingValid extends Has<ByFields, HavingFields>,
      ByEmpty extends T['by'] extends never[] ? True : False,
      InputErrors extends ByEmpty extends True
      ? `Error: "by" must not be empty.`
      : HavingValid extends False
      ? {
          [P in HavingFields]: P extends ByFields
            ? never
            : P extends string
            ? `Error: Field "${P}" used in "having" needs to be provided in "by".`
            : [
                Error,
                'Field ',
                P,
                ` in "having" needs to be provided in "by"`,
              ]
        }[HavingFields]
      : 'take' extends Keys<T>
      ? 'orderBy' extends Keys<T>
        ? ByValid extends True
          ? {}
          : {
              [P in OrderFields]: P extends ByFields
                ? never
                : `Error: Field "${P}" in "orderBy" needs to be provided in "by"`
            }[OrderFields]
        : 'Error: If you provide "take", you also need to provide "orderBy"'
      : 'skip' extends Keys<T>
      ? 'orderBy' extends Keys<T>
        ? ByValid extends True
          ? {}
          : {
              [P in OrderFields]: P extends ByFields
                ? never
                : `Error: Field "${P}" in "orderBy" needs to be provided in "by"`
            }[OrderFields]
        : 'Error: If you provide "skip", you also need to provide "orderBy"'
      : ByValid extends True
      ? {}
      : {
          [P in OrderFields]: P extends ByFields
            ? never
            : `Error: Field "${P}" in "orderBy" needs to be provided in "by"`
        }[OrderFields]
    >(args: SubsetIntersection<T, TileGroupByArgs, OrderByArg> & InputErrors): {} extends InputErrors ? GetTileGroupByPayload<T> : Prisma.PrismaPromise<InputErrors>
  /**
   * Fields of the Tile model
   */
  readonly fields: TileFieldRefs;
  }

  /**
   * The delegate class that acts as a "Promise-like" for Tile.
   * Why is this prefixed with `Prisma__`?
   * Because we want to prevent naming conflicts as mentioned in
   * https://github.com/prisma/prisma-client-js/issues/707
   */
  export interface Prisma__TileClient<T, Null = never, ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs, GlobalOmitOptions = {}> extends Prisma.PrismaPromise<T> {
    readonly [Symbol.toStringTag]: "PrismaPromise"
    board<T extends BoardDefaultArgs<ExtArgs> = {}>(args?: Subset<T, BoardDefaultArgs<ExtArgs>>): Prisma__BoardClient<$Result.GetResult<Prisma.$BoardPayload<ExtArgs>, T, "findUniqueOrThrow", GlobalOmitOptions> | Null, Null, ExtArgs, GlobalOmitOptions>
    task<T extends Tile$taskArgs<ExtArgs> = {}>(args?: Subset<T, Tile$taskArgs<ExtArgs>>): Prisma__TaskClient<$Result.GetResult<Prisma.$TaskPayload<ExtArgs>, T, "findUniqueOrThrow", GlobalOmitOptions> | null, null, ExtArgs, GlobalOmitOptions>
    completedTiles<T extends Tile$completedTilesArgs<ExtArgs> = {}>(args?: Subset<T, Tile$completedTilesArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$CompletedTilePayload<ExtArgs>, T, "findMany", GlobalOmitOptions> | Null>
    /**
     * Attaches callbacks for the resolution and/or rejection of the Promise.
     * @param onfulfilled The callback to execute when the Promise is resolved.
     * @param onrejected The callback to execute when the Promise is rejected.
     * @returns A Promise for the completion of which ever callback is executed.
     */
    then<TResult1 = T, TResult2 = never>(onfulfilled?: ((value: T) => TResult1 | PromiseLike<TResult1>) | undefined | null, onrejected?: ((reason: any) => TResult2 | PromiseLike<TResult2>) | undefined | null): $Utils.JsPromise<TResult1 | TResult2>
    /**
     * Attaches a callback for only the rejection of the Promise.
     * @param onrejected The callback to execute when the Promise is rejected.
     * @returns A Promise for the completion of the callback.
     */
    catch<TResult = never>(onrejected?: ((reason: any) => TResult | PromiseLike<TResult>) | undefined | null): $Utils.JsPromise<T | TResult>
    /**
     * Attaches a callback that is invoked when the Promise is settled (fulfilled or rejected). The
     * resolved value cannot be modified from the callback.
     * @param onfinally The callback to execute when the Promise is settled (fulfilled or rejected).
     * @returns A Promise for the completion of the callback.
     */
    finally(onfinally?: (() => void) | undefined | null): $Utils.JsPromise<T>
  }




  /**
   * Fields of the Tile model
   */
  interface TileFieldRefs {
    readonly id: FieldRef<"Tile", 'String'>
    readonly boardId: FieldRef<"Tile", 'String'>
    readonly position: FieldRef<"Tile", 'Int'>
    readonly taskId: FieldRef<"Tile", 'String'>
    readonly titleOverride: FieldRef<"Tile", 'String'>
    readonly type: FieldRef<"Tile", 'TileType'>
    readonly targetPosition: FieldRef<"Tile", 'Int'>
    readonly createdAt: FieldRef<"Tile", 'DateTime'>
    readonly updatedAt: FieldRef<"Tile", 'DateTime'>
  }
    

  // Custom InputTypes
  /**
   * Tile findUnique
   */
  export type TileFindUniqueArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Tile
     */
    select?: TileSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Tile
     */
    omit?: TileOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TileInclude<ExtArgs> | null
    /**
     * Filter, which Tile to fetch.
     */
    where: TileWhereUniqueInput
  }

  /**
   * Tile findUniqueOrThrow
   */
  export type TileFindUniqueOrThrowArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Tile
     */
    select?: TileSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Tile
     */
    omit?: TileOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TileInclude<ExtArgs> | null
    /**
     * Filter, which Tile to fetch.
     */
    where: TileWhereUniqueInput
  }

  /**
   * Tile findFirst
   */
  export type TileFindFirstArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Tile
     */
    select?: TileSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Tile
     */
    omit?: TileOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TileInclude<ExtArgs> | null
    /**
     * Filter, which Tile to fetch.
     */
    where?: TileWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of Tiles to fetch.
     */
    orderBy?: TileOrderByWithRelationInput | TileOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the position for searching for Tiles.
     */
    cursor?: TileWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` Tiles from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` Tiles.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/distinct Distinct Docs}
     * 
     * Filter by unique combinations of Tiles.
     */
    distinct?: TileScalarFieldEnum | TileScalarFieldEnum[]
  }

  /**
   * Tile findFirstOrThrow
   */
  export type TileFindFirstOrThrowArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Tile
     */
    select?: TileSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Tile
     */
    omit?: TileOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TileInclude<ExtArgs> | null
    /**
     * Filter, which Tile to fetch.
     */
    where?: TileWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of Tiles to fetch.
     */
    orderBy?: TileOrderByWithRelationInput | TileOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the position for searching for Tiles.
     */
    cursor?: TileWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` Tiles from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` Tiles.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/distinct Distinct Docs}
     * 
     * Filter by unique combinations of Tiles.
     */
    distinct?: TileScalarFieldEnum | TileScalarFieldEnum[]
  }

  /**
   * Tile findMany
   */
  export type TileFindManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Tile
     */
    select?: TileSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Tile
     */
    omit?: TileOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TileInclude<ExtArgs> | null
    /**
     * Filter, which Tiles to fetch.
     */
    where?: TileWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of Tiles to fetch.
     */
    orderBy?: TileOrderByWithRelationInput | TileOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the position for listing Tiles.
     */
    cursor?: TileWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` Tiles from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` Tiles.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/distinct Distinct Docs}
     * 
     * Filter by unique combinations of Tiles.
     */
    distinct?: TileScalarFieldEnum | TileScalarFieldEnum[]
  }

  /**
   * Tile create
   */
  export type TileCreateArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Tile
     */
    select?: TileSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Tile
     */
    omit?: TileOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TileInclude<ExtArgs> | null
    /**
     * The data needed to create a Tile.
     */
    data: XOR<TileCreateInput, TileUncheckedCreateInput>
  }

  /**
   * Tile createMany
   */
  export type TileCreateManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * The data used to create many Tiles.
     */
    data: TileCreateManyInput | TileCreateManyInput[]
    skipDuplicates?: boolean
  }

  /**
   * Tile createManyAndReturn
   */
  export type TileCreateManyAndReturnArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Tile
     */
    select?: TileSelectCreateManyAndReturn<ExtArgs> | null
    /**
     * Omit specific fields from the Tile
     */
    omit?: TileOmit<ExtArgs> | null
    /**
     * The data used to create many Tiles.
     */
    data: TileCreateManyInput | TileCreateManyInput[]
    skipDuplicates?: boolean
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TileIncludeCreateManyAndReturn<ExtArgs> | null
  }

  /**
   * Tile update
   */
  export type TileUpdateArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Tile
     */
    select?: TileSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Tile
     */
    omit?: TileOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TileInclude<ExtArgs> | null
    /**
     * The data needed to update a Tile.
     */
    data: XOR<TileUpdateInput, TileUncheckedUpdateInput>
    /**
     * Choose, which Tile to update.
     */
    where: TileWhereUniqueInput
  }

  /**
   * Tile updateMany
   */
  export type TileUpdateManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * The data used to update Tiles.
     */
    data: XOR<TileUpdateManyMutationInput, TileUncheckedUpdateManyInput>
    /**
     * Filter which Tiles to update
     */
    where?: TileWhereInput
    /**
     * Limit how many Tiles to update.
     */
    limit?: number
  }

  /**
   * Tile updateManyAndReturn
   */
  export type TileUpdateManyAndReturnArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Tile
     */
    select?: TileSelectUpdateManyAndReturn<ExtArgs> | null
    /**
     * Omit specific fields from the Tile
     */
    omit?: TileOmit<ExtArgs> | null
    /**
     * The data used to update Tiles.
     */
    data: XOR<TileUpdateManyMutationInput, TileUncheckedUpdateManyInput>
    /**
     * Filter which Tiles to update
     */
    where?: TileWhereInput
    /**
     * Limit how many Tiles to update.
     */
    limit?: number
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TileIncludeUpdateManyAndReturn<ExtArgs> | null
  }

  /**
   * Tile upsert
   */
  export type TileUpsertArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Tile
     */
    select?: TileSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Tile
     */
    omit?: TileOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TileInclude<ExtArgs> | null
    /**
     * The filter to search for the Tile to update in case it exists.
     */
    where: TileWhereUniqueInput
    /**
     * In case the Tile found by the `where` argument doesn't exist, create a new Tile with this data.
     */
    create: XOR<TileCreateInput, TileUncheckedCreateInput>
    /**
     * In case the Tile was found with the provided `where` argument, update it with this data.
     */
    update: XOR<TileUpdateInput, TileUncheckedUpdateInput>
  }

  /**
   * Tile delete
   */
  export type TileDeleteArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Tile
     */
    select?: TileSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Tile
     */
    omit?: TileOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TileInclude<ExtArgs> | null
    /**
     * Filter which Tile to delete.
     */
    where: TileWhereUniqueInput
  }

  /**
   * Tile deleteMany
   */
  export type TileDeleteManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Filter which Tiles to delete
     */
    where?: TileWhereInput
    /**
     * Limit how many Tiles to delete.
     */
    limit?: number
  }

  /**
   * Tile.task
   */
  export type Tile$taskArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Task
     */
    select?: TaskSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Task
     */
    omit?: TaskOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TaskInclude<ExtArgs> | null
    where?: TaskWhereInput
  }

  /**
   * Tile.completedTiles
   */
  export type Tile$completedTilesArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the CompletedTile
     */
    select?: CompletedTileSelect<ExtArgs> | null
    /**
     * Omit specific fields from the CompletedTile
     */
    omit?: CompletedTileOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: CompletedTileInclude<ExtArgs> | null
    where?: CompletedTileWhereInput
    orderBy?: CompletedTileOrderByWithRelationInput | CompletedTileOrderByWithRelationInput[]
    cursor?: CompletedTileWhereUniqueInput
    take?: number
    skip?: number
    distinct?: CompletedTileScalarFieldEnum | CompletedTileScalarFieldEnum[]
  }

  /**
   * Tile without action
   */
  export type TileDefaultArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Tile
     */
    select?: TileSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Tile
     */
    omit?: TileOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TileInclude<ExtArgs> | null
  }


  /**
   * Model PlayerBoard
   */

  export type AggregatePlayerBoard = {
    _count: PlayerBoardCountAggregateOutputType | null
    _avg: PlayerBoardAvgAggregateOutputType | null
    _sum: PlayerBoardSumAggregateOutputType | null
    _min: PlayerBoardMinAggregateOutputType | null
    _max: PlayerBoardMaxAggregateOutputType | null
  }

  export type PlayerBoardAvgAggregateOutputType = {
    currentPosition: number | null
    diceRollsToday: number | null
  }

  export type PlayerBoardSumAggregateOutputType = {
    currentPosition: number | null
    diceRollsToday: number | null
  }

  export type PlayerBoardMinAggregateOutputType = {
    id: string | null
    userId: string | null
    boardId: string | null
    teamId: string | null
    currentPosition: number | null
    diceRollsToday: number | null
    lastRollDate: Date | null
    createdAt: Date | null
    updatedAt: Date | null
  }

  export type PlayerBoardMaxAggregateOutputType = {
    id: string | null
    userId: string | null
    boardId: string | null
    teamId: string | null
    currentPosition: number | null
    diceRollsToday: number | null
    lastRollDate: Date | null
    createdAt: Date | null
    updatedAt: Date | null
  }

  export type PlayerBoardCountAggregateOutputType = {
    id: number
    userId: number
    boardId: number
    teamId: number
    currentPosition: number
    diceRollsToday: number
    lastRollDate: number
    createdAt: number
    updatedAt: number
    _all: number
  }


  export type PlayerBoardAvgAggregateInputType = {
    currentPosition?: true
    diceRollsToday?: true
  }

  export type PlayerBoardSumAggregateInputType = {
    currentPosition?: true
    diceRollsToday?: true
  }

  export type PlayerBoardMinAggregateInputType = {
    id?: true
    userId?: true
    boardId?: true
    teamId?: true
    currentPosition?: true
    diceRollsToday?: true
    lastRollDate?: true
    createdAt?: true
    updatedAt?: true
  }

  export type PlayerBoardMaxAggregateInputType = {
    id?: true
    userId?: true
    boardId?: true
    teamId?: true
    currentPosition?: true
    diceRollsToday?: true
    lastRollDate?: true
    createdAt?: true
    updatedAt?: true
  }

  export type PlayerBoardCountAggregateInputType = {
    id?: true
    userId?: true
    boardId?: true
    teamId?: true
    currentPosition?: true
    diceRollsToday?: true
    lastRollDate?: true
    createdAt?: true
    updatedAt?: true
    _all?: true
  }

  export type PlayerBoardAggregateArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Filter which PlayerBoard to aggregate.
     */
    where?: PlayerBoardWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of PlayerBoards to fetch.
     */
    orderBy?: PlayerBoardOrderByWithRelationInput | PlayerBoardOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the start position
     */
    cursor?: PlayerBoardWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` PlayerBoards from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` PlayerBoards.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Count returned PlayerBoards
    **/
    _count?: true | PlayerBoardCountAggregateInputType
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Select which fields to average
    **/
    _avg?: PlayerBoardAvgAggregateInputType
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Select which fields to sum
    **/
    _sum?: PlayerBoardSumAggregateInputType
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Select which fields to find the minimum value
    **/
    _min?: PlayerBoardMinAggregateInputType
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Select which fields to find the maximum value
    **/
    _max?: PlayerBoardMaxAggregateInputType
  }

  export type GetPlayerBoardAggregateType<T extends PlayerBoardAggregateArgs> = {
        [P in keyof T & keyof AggregatePlayerBoard]: P extends '_count' | 'count'
      ? T[P] extends true
        ? number
        : GetScalarType<T[P], AggregatePlayerBoard[P]>
      : GetScalarType<T[P], AggregatePlayerBoard[P]>
  }




  export type PlayerBoardGroupByArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    where?: PlayerBoardWhereInput
    orderBy?: PlayerBoardOrderByWithAggregationInput | PlayerBoardOrderByWithAggregationInput[]
    by: PlayerBoardScalarFieldEnum[] | PlayerBoardScalarFieldEnum
    having?: PlayerBoardScalarWhereWithAggregatesInput
    take?: number
    skip?: number
    _count?: PlayerBoardCountAggregateInputType | true
    _avg?: PlayerBoardAvgAggregateInputType
    _sum?: PlayerBoardSumAggregateInputType
    _min?: PlayerBoardMinAggregateInputType
    _max?: PlayerBoardMaxAggregateInputType
  }

  export type PlayerBoardGroupByOutputType = {
    id: string
    userId: string
    boardId: string
    teamId: string | null
    currentPosition: number
    diceRollsToday: number
    lastRollDate: Date | null
    createdAt: Date
    updatedAt: Date
    _count: PlayerBoardCountAggregateOutputType | null
    _avg: PlayerBoardAvgAggregateOutputType | null
    _sum: PlayerBoardSumAggregateOutputType | null
    _min: PlayerBoardMinAggregateOutputType | null
    _max: PlayerBoardMaxAggregateOutputType | null
  }

  type GetPlayerBoardGroupByPayload<T extends PlayerBoardGroupByArgs> = Prisma.PrismaPromise<
    Array<
      PickEnumerable<PlayerBoardGroupByOutputType, T['by']> &
        {
          [P in ((keyof T) & (keyof PlayerBoardGroupByOutputType))]: P extends '_count'
            ? T[P] extends boolean
              ? number
              : GetScalarType<T[P], PlayerBoardGroupByOutputType[P]>
            : GetScalarType<T[P], PlayerBoardGroupByOutputType[P]>
        }
      >
    >


  export type PlayerBoardSelect<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetSelect<{
    id?: boolean
    userId?: boolean
    boardId?: boolean
    teamId?: boolean
    currentPosition?: boolean
    diceRollsToday?: boolean
    lastRollDate?: boolean
    createdAt?: boolean
    updatedAt?: boolean
    user?: boolean | UserDefaultArgs<ExtArgs>
    board?: boolean | BoardDefaultArgs<ExtArgs>
    team?: boolean | PlayerBoard$teamArgs<ExtArgs>
    completedTiles?: boolean | PlayerBoard$completedTilesArgs<ExtArgs>
    _count?: boolean | PlayerBoardCountOutputTypeDefaultArgs<ExtArgs>
  }, ExtArgs["result"]["playerBoard"]>

  export type PlayerBoardSelectCreateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetSelect<{
    id?: boolean
    userId?: boolean
    boardId?: boolean
    teamId?: boolean
    currentPosition?: boolean
    diceRollsToday?: boolean
    lastRollDate?: boolean
    createdAt?: boolean
    updatedAt?: boolean
    user?: boolean | UserDefaultArgs<ExtArgs>
    board?: boolean | BoardDefaultArgs<ExtArgs>
    team?: boolean | PlayerBoard$teamArgs<ExtArgs>
  }, ExtArgs["result"]["playerBoard"]>

  export type PlayerBoardSelectUpdateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetSelect<{
    id?: boolean
    userId?: boolean
    boardId?: boolean
    teamId?: boolean
    currentPosition?: boolean
    diceRollsToday?: boolean
    lastRollDate?: boolean
    createdAt?: boolean
    updatedAt?: boolean
    user?: boolean | UserDefaultArgs<ExtArgs>
    board?: boolean | BoardDefaultArgs<ExtArgs>
    team?: boolean | PlayerBoard$teamArgs<ExtArgs>
  }, ExtArgs["result"]["playerBoard"]>

  export type PlayerBoardSelectScalar = {
    id?: boolean
    userId?: boolean
    boardId?: boolean
    teamId?: boolean
    currentPosition?: boolean
    diceRollsToday?: boolean
    lastRollDate?: boolean
    createdAt?: boolean
    updatedAt?: boolean
  }

  export type PlayerBoardOmit<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetOmit<"id" | "userId" | "boardId" | "teamId" | "currentPosition" | "diceRollsToday" | "lastRollDate" | "createdAt" | "updatedAt", ExtArgs["result"]["playerBoard"]>
  export type PlayerBoardInclude<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    user?: boolean | UserDefaultArgs<ExtArgs>
    board?: boolean | BoardDefaultArgs<ExtArgs>
    team?: boolean | PlayerBoard$teamArgs<ExtArgs>
    completedTiles?: boolean | PlayerBoard$completedTilesArgs<ExtArgs>
    _count?: boolean | PlayerBoardCountOutputTypeDefaultArgs<ExtArgs>
  }
  export type PlayerBoardIncludeCreateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    user?: boolean | UserDefaultArgs<ExtArgs>
    board?: boolean | BoardDefaultArgs<ExtArgs>
    team?: boolean | PlayerBoard$teamArgs<ExtArgs>
  }
  export type PlayerBoardIncludeUpdateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    user?: boolean | UserDefaultArgs<ExtArgs>
    board?: boolean | BoardDefaultArgs<ExtArgs>
    team?: boolean | PlayerBoard$teamArgs<ExtArgs>
  }

  export type $PlayerBoardPayload<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    name: "PlayerBoard"
    objects: {
      user: Prisma.$UserPayload<ExtArgs>
      board: Prisma.$BoardPayload<ExtArgs>
      team: Prisma.$TeamPayload<ExtArgs> | null
      completedTiles: Prisma.$CompletedTilePayload<ExtArgs>[]
    }
    scalars: $Extensions.GetPayloadResult<{
      id: string
      userId: string
      boardId: string
      teamId: string | null
      currentPosition: number
      diceRollsToday: number
      lastRollDate: Date | null
      createdAt: Date
      updatedAt: Date
    }, ExtArgs["result"]["playerBoard"]>
    composites: {}
  }

  type PlayerBoardGetPayload<S extends boolean | null | undefined | PlayerBoardDefaultArgs> = $Result.GetResult<Prisma.$PlayerBoardPayload, S>

  type PlayerBoardCountArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> =
    Omit<PlayerBoardFindManyArgs, 'select' | 'include' | 'distinct' | 'omit'> & {
      select?: PlayerBoardCountAggregateInputType | true
    }

  export interface PlayerBoardDelegate<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs, GlobalOmitOptions = {}> {
    [K: symbol]: { types: Prisma.TypeMap<ExtArgs>['model']['PlayerBoard'], meta: { name: 'PlayerBoard' } }
    /**
     * Find zero or one PlayerBoard that matches the filter.
     * @param {PlayerBoardFindUniqueArgs} args - Arguments to find a PlayerBoard
     * @example
     * // Get one PlayerBoard
     * const playerBoard = await prisma.playerBoard.findUnique({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findUnique<T extends PlayerBoardFindUniqueArgs>(args: SelectSubset<T, PlayerBoardFindUniqueArgs<ExtArgs>>): Prisma__PlayerBoardClient<$Result.GetResult<Prisma.$PlayerBoardPayload<ExtArgs>, T, "findUnique", GlobalOmitOptions> | null, null, ExtArgs, GlobalOmitOptions>

    /**
     * Find one PlayerBoard that matches the filter or throw an error with `error.code='P2025'`
     * if no matches were found.
     * @param {PlayerBoardFindUniqueOrThrowArgs} args - Arguments to find a PlayerBoard
     * @example
     * // Get one PlayerBoard
     * const playerBoard = await prisma.playerBoard.findUniqueOrThrow({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findUniqueOrThrow<T extends PlayerBoardFindUniqueOrThrowArgs>(args: SelectSubset<T, PlayerBoardFindUniqueOrThrowArgs<ExtArgs>>): Prisma__PlayerBoardClient<$Result.GetResult<Prisma.$PlayerBoardPayload<ExtArgs>, T, "findUniqueOrThrow", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Find the first PlayerBoard that matches the filter.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {PlayerBoardFindFirstArgs} args - Arguments to find a PlayerBoard
     * @example
     * // Get one PlayerBoard
     * const playerBoard = await prisma.playerBoard.findFirst({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findFirst<T extends PlayerBoardFindFirstArgs>(args?: SelectSubset<T, PlayerBoardFindFirstArgs<ExtArgs>>): Prisma__PlayerBoardClient<$Result.GetResult<Prisma.$PlayerBoardPayload<ExtArgs>, T, "findFirst", GlobalOmitOptions> | null, null, ExtArgs, GlobalOmitOptions>

    /**
     * Find the first PlayerBoard that matches the filter or
     * throw `PrismaKnownClientError` with `P2025` code if no matches were found.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {PlayerBoardFindFirstOrThrowArgs} args - Arguments to find a PlayerBoard
     * @example
     * // Get one PlayerBoard
     * const playerBoard = await prisma.playerBoard.findFirstOrThrow({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findFirstOrThrow<T extends PlayerBoardFindFirstOrThrowArgs>(args?: SelectSubset<T, PlayerBoardFindFirstOrThrowArgs<ExtArgs>>): Prisma__PlayerBoardClient<$Result.GetResult<Prisma.$PlayerBoardPayload<ExtArgs>, T, "findFirstOrThrow", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Find zero or more PlayerBoards that matches the filter.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {PlayerBoardFindManyArgs} args - Arguments to filter and select certain fields only.
     * @example
     * // Get all PlayerBoards
     * const playerBoards = await prisma.playerBoard.findMany()
     * 
     * // Get first 10 PlayerBoards
     * const playerBoards = await prisma.playerBoard.findMany({ take: 10 })
     * 
     * // Only select the `id`
     * const playerBoardWithIdOnly = await prisma.playerBoard.findMany({ select: { id: true } })
     * 
     */
    findMany<T extends PlayerBoardFindManyArgs>(args?: SelectSubset<T, PlayerBoardFindManyArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$PlayerBoardPayload<ExtArgs>, T, "findMany", GlobalOmitOptions>>

    /**
     * Create a PlayerBoard.
     * @param {PlayerBoardCreateArgs} args - Arguments to create a PlayerBoard.
     * @example
     * // Create one PlayerBoard
     * const PlayerBoard = await prisma.playerBoard.create({
     *   data: {
     *     // ... data to create a PlayerBoard
     *   }
     * })
     * 
     */
    create<T extends PlayerBoardCreateArgs>(args: SelectSubset<T, PlayerBoardCreateArgs<ExtArgs>>): Prisma__PlayerBoardClient<$Result.GetResult<Prisma.$PlayerBoardPayload<ExtArgs>, T, "create", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Create many PlayerBoards.
     * @param {PlayerBoardCreateManyArgs} args - Arguments to create many PlayerBoards.
     * @example
     * // Create many PlayerBoards
     * const playerBoard = await prisma.playerBoard.createMany({
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     *     
     */
    createMany<T extends PlayerBoardCreateManyArgs>(args?: SelectSubset<T, PlayerBoardCreateManyArgs<ExtArgs>>): Prisma.PrismaPromise<BatchPayload>

    /**
     * Create many PlayerBoards and returns the data saved in the database.
     * @param {PlayerBoardCreateManyAndReturnArgs} args - Arguments to create many PlayerBoards.
     * @example
     * // Create many PlayerBoards
     * const playerBoard = await prisma.playerBoard.createManyAndReturn({
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * 
     * // Create many PlayerBoards and only return the `id`
     * const playerBoardWithIdOnly = await prisma.playerBoard.createManyAndReturn({
     *   select: { id: true },
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * 
     */
    createManyAndReturn<T extends PlayerBoardCreateManyAndReturnArgs>(args?: SelectSubset<T, PlayerBoardCreateManyAndReturnArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$PlayerBoardPayload<ExtArgs>, T, "createManyAndReturn", GlobalOmitOptions>>

    /**
     * Delete a PlayerBoard.
     * @param {PlayerBoardDeleteArgs} args - Arguments to delete one PlayerBoard.
     * @example
     * // Delete one PlayerBoard
     * const PlayerBoard = await prisma.playerBoard.delete({
     *   where: {
     *     // ... filter to delete one PlayerBoard
     *   }
     * })
     * 
     */
    delete<T extends PlayerBoardDeleteArgs>(args: SelectSubset<T, PlayerBoardDeleteArgs<ExtArgs>>): Prisma__PlayerBoardClient<$Result.GetResult<Prisma.$PlayerBoardPayload<ExtArgs>, T, "delete", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Update one PlayerBoard.
     * @param {PlayerBoardUpdateArgs} args - Arguments to update one PlayerBoard.
     * @example
     * // Update one PlayerBoard
     * const playerBoard = await prisma.playerBoard.update({
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: {
     *     // ... provide data here
     *   }
     * })
     * 
     */
    update<T extends PlayerBoardUpdateArgs>(args: SelectSubset<T, PlayerBoardUpdateArgs<ExtArgs>>): Prisma__PlayerBoardClient<$Result.GetResult<Prisma.$PlayerBoardPayload<ExtArgs>, T, "update", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Delete zero or more PlayerBoards.
     * @param {PlayerBoardDeleteManyArgs} args - Arguments to filter PlayerBoards to delete.
     * @example
     * // Delete a few PlayerBoards
     * const { count } = await prisma.playerBoard.deleteMany({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     * 
     */
    deleteMany<T extends PlayerBoardDeleteManyArgs>(args?: SelectSubset<T, PlayerBoardDeleteManyArgs<ExtArgs>>): Prisma.PrismaPromise<BatchPayload>

    /**
     * Update zero or more PlayerBoards.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {PlayerBoardUpdateManyArgs} args - Arguments to update one or more rows.
     * @example
     * // Update many PlayerBoards
     * const playerBoard = await prisma.playerBoard.updateMany({
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: {
     *     // ... provide data here
     *   }
     * })
     * 
     */
    updateMany<T extends PlayerBoardUpdateManyArgs>(args: SelectSubset<T, PlayerBoardUpdateManyArgs<ExtArgs>>): Prisma.PrismaPromise<BatchPayload>

    /**
     * Update zero or more PlayerBoards and returns the data updated in the database.
     * @param {PlayerBoardUpdateManyAndReturnArgs} args - Arguments to update many PlayerBoards.
     * @example
     * // Update many PlayerBoards
     * const playerBoard = await prisma.playerBoard.updateManyAndReturn({
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * 
     * // Update zero or more PlayerBoards and only return the `id`
     * const playerBoardWithIdOnly = await prisma.playerBoard.updateManyAndReturn({
     *   select: { id: true },
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * 
     */
    updateManyAndReturn<T extends PlayerBoardUpdateManyAndReturnArgs>(args: SelectSubset<T, PlayerBoardUpdateManyAndReturnArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$PlayerBoardPayload<ExtArgs>, T, "updateManyAndReturn", GlobalOmitOptions>>

    /**
     * Create or update one PlayerBoard.
     * @param {PlayerBoardUpsertArgs} args - Arguments to update or create a PlayerBoard.
     * @example
     * // Update or create a PlayerBoard
     * const playerBoard = await prisma.playerBoard.upsert({
     *   create: {
     *     // ... data to create a PlayerBoard
     *   },
     *   update: {
     *     // ... in case it already exists, update
     *   },
     *   where: {
     *     // ... the filter for the PlayerBoard we want to update
     *   }
     * })
     */
    upsert<T extends PlayerBoardUpsertArgs>(args: SelectSubset<T, PlayerBoardUpsertArgs<ExtArgs>>): Prisma__PlayerBoardClient<$Result.GetResult<Prisma.$PlayerBoardPayload<ExtArgs>, T, "upsert", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>


    /**
     * Count the number of PlayerBoards.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {PlayerBoardCountArgs} args - Arguments to filter PlayerBoards to count.
     * @example
     * // Count the number of PlayerBoards
     * const count = await prisma.playerBoard.count({
     *   where: {
     *     // ... the filter for the PlayerBoards we want to count
     *   }
     * })
    **/
    count<T extends PlayerBoardCountArgs>(
      args?: Subset<T, PlayerBoardCountArgs>,
    ): Prisma.PrismaPromise<
      T extends $Utils.Record<'select', any>
        ? T['select'] extends true
          ? number
          : GetScalarType<T['select'], PlayerBoardCountAggregateOutputType>
        : number
    >

    /**
     * Allows you to perform aggregations operations on a PlayerBoard.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {PlayerBoardAggregateArgs} args - Select which aggregations you would like to apply and on what fields.
     * @example
     * // Ordered by age ascending
     * // Where email contains prisma.io
     * // Limited to the 10 users
     * const aggregations = await prisma.user.aggregate({
     *   _avg: {
     *     age: true,
     *   },
     *   where: {
     *     email: {
     *       contains: "prisma.io",
     *     },
     *   },
     *   orderBy: {
     *     age: "asc",
     *   },
     *   take: 10,
     * })
    **/
    aggregate<T extends PlayerBoardAggregateArgs>(args: Subset<T, PlayerBoardAggregateArgs>): Prisma.PrismaPromise<GetPlayerBoardAggregateType<T>>

    /**
     * Group by PlayerBoard.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {PlayerBoardGroupByArgs} args - Group by arguments.
     * @example
     * // Group by city, order by createdAt, get count
     * const result = await prisma.user.groupBy({
     *   by: ['city', 'createdAt'],
     *   orderBy: {
     *     createdAt: true
     *   },
     *   _count: {
     *     _all: true
     *   },
     * })
     * 
    **/
    groupBy<
      T extends PlayerBoardGroupByArgs,
      HasSelectOrTake extends Or<
        Extends<'skip', Keys<T>>,
        Extends<'take', Keys<T>>
      >,
      OrderByArg extends True extends HasSelectOrTake
        ? { orderBy: PlayerBoardGroupByArgs['orderBy'] }
        : { orderBy?: PlayerBoardGroupByArgs['orderBy'] },
      OrderFields extends ExcludeUnderscoreKeys<Keys<MaybeTupleToUnion<T['orderBy']>>>,
      ByFields extends MaybeTupleToUnion<T['by']>,
      ByValid extends Has<ByFields, OrderFields>,
      HavingFields extends GetHavingFields<T['having']>,
      HavingValid extends Has<ByFields, HavingFields>,
      ByEmpty extends T['by'] extends never[] ? True : False,
      InputErrors extends ByEmpty extends True
      ? `Error: "by" must not be empty.`
      : HavingValid extends False
      ? {
          [P in HavingFields]: P extends ByFields
            ? never
            : P extends string
            ? `Error: Field "${P}" used in "having" needs to be provided in "by".`
            : [
                Error,
                'Field ',
                P,
                ` in "having" needs to be provided in "by"`,
              ]
        }[HavingFields]
      : 'take' extends Keys<T>
      ? 'orderBy' extends Keys<T>
        ? ByValid extends True
          ? {}
          : {
              [P in OrderFields]: P extends ByFields
                ? never
                : `Error: Field "${P}" in "orderBy" needs to be provided in "by"`
            }[OrderFields]
        : 'Error: If you provide "take", you also need to provide "orderBy"'
      : 'skip' extends Keys<T>
      ? 'orderBy' extends Keys<T>
        ? ByValid extends True
          ? {}
          : {
              [P in OrderFields]: P extends ByFields
                ? never
                : `Error: Field "${P}" in "orderBy" needs to be provided in "by"`
            }[OrderFields]
        : 'Error: If you provide "skip", you also need to provide "orderBy"'
      : ByValid extends True
      ? {}
      : {
          [P in OrderFields]: P extends ByFields
            ? never
            : `Error: Field "${P}" in "orderBy" needs to be provided in "by"`
        }[OrderFields]
    >(args: SubsetIntersection<T, PlayerBoardGroupByArgs, OrderByArg> & InputErrors): {} extends InputErrors ? GetPlayerBoardGroupByPayload<T> : Prisma.PrismaPromise<InputErrors>
  /**
   * Fields of the PlayerBoard model
   */
  readonly fields: PlayerBoardFieldRefs;
  }

  /**
   * The delegate class that acts as a "Promise-like" for PlayerBoard.
   * Why is this prefixed with `Prisma__`?
   * Because we want to prevent naming conflicts as mentioned in
   * https://github.com/prisma/prisma-client-js/issues/707
   */
  export interface Prisma__PlayerBoardClient<T, Null = never, ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs, GlobalOmitOptions = {}> extends Prisma.PrismaPromise<T> {
    readonly [Symbol.toStringTag]: "PrismaPromise"
    user<T extends UserDefaultArgs<ExtArgs> = {}>(args?: Subset<T, UserDefaultArgs<ExtArgs>>): Prisma__UserClient<$Result.GetResult<Prisma.$UserPayload<ExtArgs>, T, "findUniqueOrThrow", GlobalOmitOptions> | Null, Null, ExtArgs, GlobalOmitOptions>
    board<T extends BoardDefaultArgs<ExtArgs> = {}>(args?: Subset<T, BoardDefaultArgs<ExtArgs>>): Prisma__BoardClient<$Result.GetResult<Prisma.$BoardPayload<ExtArgs>, T, "findUniqueOrThrow", GlobalOmitOptions> | Null, Null, ExtArgs, GlobalOmitOptions>
    team<T extends PlayerBoard$teamArgs<ExtArgs> = {}>(args?: Subset<T, PlayerBoard$teamArgs<ExtArgs>>): Prisma__TeamClient<$Result.GetResult<Prisma.$TeamPayload<ExtArgs>, T, "findUniqueOrThrow", GlobalOmitOptions> | null, null, ExtArgs, GlobalOmitOptions>
    completedTiles<T extends PlayerBoard$completedTilesArgs<ExtArgs> = {}>(args?: Subset<T, PlayerBoard$completedTilesArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$CompletedTilePayload<ExtArgs>, T, "findMany", GlobalOmitOptions> | Null>
    /**
     * Attaches callbacks for the resolution and/or rejection of the Promise.
     * @param onfulfilled The callback to execute when the Promise is resolved.
     * @param onrejected The callback to execute when the Promise is rejected.
     * @returns A Promise for the completion of which ever callback is executed.
     */
    then<TResult1 = T, TResult2 = never>(onfulfilled?: ((value: T) => TResult1 | PromiseLike<TResult1>) | undefined | null, onrejected?: ((reason: any) => TResult2 | PromiseLike<TResult2>) | undefined | null): $Utils.JsPromise<TResult1 | TResult2>
    /**
     * Attaches a callback for only the rejection of the Promise.
     * @param onrejected The callback to execute when the Promise is rejected.
     * @returns A Promise for the completion of the callback.
     */
    catch<TResult = never>(onrejected?: ((reason: any) => TResult | PromiseLike<TResult>) | undefined | null): $Utils.JsPromise<T | TResult>
    /**
     * Attaches a callback that is invoked when the Promise is settled (fulfilled or rejected). The
     * resolved value cannot be modified from the callback.
     * @param onfinally The callback to execute when the Promise is settled (fulfilled or rejected).
     * @returns A Promise for the completion of the callback.
     */
    finally(onfinally?: (() => void) | undefined | null): $Utils.JsPromise<T>
  }




  /**
   * Fields of the PlayerBoard model
   */
  interface PlayerBoardFieldRefs {
    readonly id: FieldRef<"PlayerBoard", 'String'>
    readonly userId: FieldRef<"PlayerBoard", 'String'>
    readonly boardId: FieldRef<"PlayerBoard", 'String'>
    readonly teamId: FieldRef<"PlayerBoard", 'String'>
    readonly currentPosition: FieldRef<"PlayerBoard", 'Int'>
    readonly diceRollsToday: FieldRef<"PlayerBoard", 'Int'>
    readonly lastRollDate: FieldRef<"PlayerBoard", 'DateTime'>
    readonly createdAt: FieldRef<"PlayerBoard", 'DateTime'>
    readonly updatedAt: FieldRef<"PlayerBoard", 'DateTime'>
  }
    

  // Custom InputTypes
  /**
   * PlayerBoard findUnique
   */
  export type PlayerBoardFindUniqueArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the PlayerBoard
     */
    select?: PlayerBoardSelect<ExtArgs> | null
    /**
     * Omit specific fields from the PlayerBoard
     */
    omit?: PlayerBoardOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: PlayerBoardInclude<ExtArgs> | null
    /**
     * Filter, which PlayerBoard to fetch.
     */
    where: PlayerBoardWhereUniqueInput
  }

  /**
   * PlayerBoard findUniqueOrThrow
   */
  export type PlayerBoardFindUniqueOrThrowArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the PlayerBoard
     */
    select?: PlayerBoardSelect<ExtArgs> | null
    /**
     * Omit specific fields from the PlayerBoard
     */
    omit?: PlayerBoardOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: PlayerBoardInclude<ExtArgs> | null
    /**
     * Filter, which PlayerBoard to fetch.
     */
    where: PlayerBoardWhereUniqueInput
  }

  /**
   * PlayerBoard findFirst
   */
  export type PlayerBoardFindFirstArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the PlayerBoard
     */
    select?: PlayerBoardSelect<ExtArgs> | null
    /**
     * Omit specific fields from the PlayerBoard
     */
    omit?: PlayerBoardOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: PlayerBoardInclude<ExtArgs> | null
    /**
     * Filter, which PlayerBoard to fetch.
     */
    where?: PlayerBoardWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of PlayerBoards to fetch.
     */
    orderBy?: PlayerBoardOrderByWithRelationInput | PlayerBoardOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the position for searching for PlayerBoards.
     */
    cursor?: PlayerBoardWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` PlayerBoards from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` PlayerBoards.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/distinct Distinct Docs}
     * 
     * Filter by unique combinations of PlayerBoards.
     */
    distinct?: PlayerBoardScalarFieldEnum | PlayerBoardScalarFieldEnum[]
  }

  /**
   * PlayerBoard findFirstOrThrow
   */
  export type PlayerBoardFindFirstOrThrowArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the PlayerBoard
     */
    select?: PlayerBoardSelect<ExtArgs> | null
    /**
     * Omit specific fields from the PlayerBoard
     */
    omit?: PlayerBoardOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: PlayerBoardInclude<ExtArgs> | null
    /**
     * Filter, which PlayerBoard to fetch.
     */
    where?: PlayerBoardWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of PlayerBoards to fetch.
     */
    orderBy?: PlayerBoardOrderByWithRelationInput | PlayerBoardOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the position for searching for PlayerBoards.
     */
    cursor?: PlayerBoardWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` PlayerBoards from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` PlayerBoards.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/distinct Distinct Docs}
     * 
     * Filter by unique combinations of PlayerBoards.
     */
    distinct?: PlayerBoardScalarFieldEnum | PlayerBoardScalarFieldEnum[]
  }

  /**
   * PlayerBoard findMany
   */
  export type PlayerBoardFindManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the PlayerBoard
     */
    select?: PlayerBoardSelect<ExtArgs> | null
    /**
     * Omit specific fields from the PlayerBoard
     */
    omit?: PlayerBoardOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: PlayerBoardInclude<ExtArgs> | null
    /**
     * Filter, which PlayerBoards to fetch.
     */
    where?: PlayerBoardWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of PlayerBoards to fetch.
     */
    orderBy?: PlayerBoardOrderByWithRelationInput | PlayerBoardOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the position for listing PlayerBoards.
     */
    cursor?: PlayerBoardWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` PlayerBoards from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` PlayerBoards.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/distinct Distinct Docs}
     * 
     * Filter by unique combinations of PlayerBoards.
     */
    distinct?: PlayerBoardScalarFieldEnum | PlayerBoardScalarFieldEnum[]
  }

  /**
   * PlayerBoard create
   */
  export type PlayerBoardCreateArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the PlayerBoard
     */
    select?: PlayerBoardSelect<ExtArgs> | null
    /**
     * Omit specific fields from the PlayerBoard
     */
    omit?: PlayerBoardOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: PlayerBoardInclude<ExtArgs> | null
    /**
     * The data needed to create a PlayerBoard.
     */
    data: XOR<PlayerBoardCreateInput, PlayerBoardUncheckedCreateInput>
  }

  /**
   * PlayerBoard createMany
   */
  export type PlayerBoardCreateManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * The data used to create many PlayerBoards.
     */
    data: PlayerBoardCreateManyInput | PlayerBoardCreateManyInput[]
    skipDuplicates?: boolean
  }

  /**
   * PlayerBoard createManyAndReturn
   */
  export type PlayerBoardCreateManyAndReturnArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the PlayerBoard
     */
    select?: PlayerBoardSelectCreateManyAndReturn<ExtArgs> | null
    /**
     * Omit specific fields from the PlayerBoard
     */
    omit?: PlayerBoardOmit<ExtArgs> | null
    /**
     * The data used to create many PlayerBoards.
     */
    data: PlayerBoardCreateManyInput | PlayerBoardCreateManyInput[]
    skipDuplicates?: boolean
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: PlayerBoardIncludeCreateManyAndReturn<ExtArgs> | null
  }

  /**
   * PlayerBoard update
   */
  export type PlayerBoardUpdateArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the PlayerBoard
     */
    select?: PlayerBoardSelect<ExtArgs> | null
    /**
     * Omit specific fields from the PlayerBoard
     */
    omit?: PlayerBoardOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: PlayerBoardInclude<ExtArgs> | null
    /**
     * The data needed to update a PlayerBoard.
     */
    data: XOR<PlayerBoardUpdateInput, PlayerBoardUncheckedUpdateInput>
    /**
     * Choose, which PlayerBoard to update.
     */
    where: PlayerBoardWhereUniqueInput
  }

  /**
   * PlayerBoard updateMany
   */
  export type PlayerBoardUpdateManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * The data used to update PlayerBoards.
     */
    data: XOR<PlayerBoardUpdateManyMutationInput, PlayerBoardUncheckedUpdateManyInput>
    /**
     * Filter which PlayerBoards to update
     */
    where?: PlayerBoardWhereInput
    /**
     * Limit how many PlayerBoards to update.
     */
    limit?: number
  }

  /**
   * PlayerBoard updateManyAndReturn
   */
  export type PlayerBoardUpdateManyAndReturnArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the PlayerBoard
     */
    select?: PlayerBoardSelectUpdateManyAndReturn<ExtArgs> | null
    /**
     * Omit specific fields from the PlayerBoard
     */
    omit?: PlayerBoardOmit<ExtArgs> | null
    /**
     * The data used to update PlayerBoards.
     */
    data: XOR<PlayerBoardUpdateManyMutationInput, PlayerBoardUncheckedUpdateManyInput>
    /**
     * Filter which PlayerBoards to update
     */
    where?: PlayerBoardWhereInput
    /**
     * Limit how many PlayerBoards to update.
     */
    limit?: number
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: PlayerBoardIncludeUpdateManyAndReturn<ExtArgs> | null
  }

  /**
   * PlayerBoard upsert
   */
  export type PlayerBoardUpsertArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the PlayerBoard
     */
    select?: PlayerBoardSelect<ExtArgs> | null
    /**
     * Omit specific fields from the PlayerBoard
     */
    omit?: PlayerBoardOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: PlayerBoardInclude<ExtArgs> | null
    /**
     * The filter to search for the PlayerBoard to update in case it exists.
     */
    where: PlayerBoardWhereUniqueInput
    /**
     * In case the PlayerBoard found by the `where` argument doesn't exist, create a new PlayerBoard with this data.
     */
    create: XOR<PlayerBoardCreateInput, PlayerBoardUncheckedCreateInput>
    /**
     * In case the PlayerBoard was found with the provided `where` argument, update it with this data.
     */
    update: XOR<PlayerBoardUpdateInput, PlayerBoardUncheckedUpdateInput>
  }

  /**
   * PlayerBoard delete
   */
  export type PlayerBoardDeleteArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the PlayerBoard
     */
    select?: PlayerBoardSelect<ExtArgs> | null
    /**
     * Omit specific fields from the PlayerBoard
     */
    omit?: PlayerBoardOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: PlayerBoardInclude<ExtArgs> | null
    /**
     * Filter which PlayerBoard to delete.
     */
    where: PlayerBoardWhereUniqueInput
  }

  /**
   * PlayerBoard deleteMany
   */
  export type PlayerBoardDeleteManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Filter which PlayerBoards to delete
     */
    where?: PlayerBoardWhereInput
    /**
     * Limit how many PlayerBoards to delete.
     */
    limit?: number
  }

  /**
   * PlayerBoard.team
   */
  export type PlayerBoard$teamArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Team
     */
    select?: TeamSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Team
     */
    omit?: TeamOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TeamInclude<ExtArgs> | null
    where?: TeamWhereInput
  }

  /**
   * PlayerBoard.completedTiles
   */
  export type PlayerBoard$completedTilesArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the CompletedTile
     */
    select?: CompletedTileSelect<ExtArgs> | null
    /**
     * Omit specific fields from the CompletedTile
     */
    omit?: CompletedTileOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: CompletedTileInclude<ExtArgs> | null
    where?: CompletedTileWhereInput
    orderBy?: CompletedTileOrderByWithRelationInput | CompletedTileOrderByWithRelationInput[]
    cursor?: CompletedTileWhereUniqueInput
    take?: number
    skip?: number
    distinct?: CompletedTileScalarFieldEnum | CompletedTileScalarFieldEnum[]
  }

  /**
   * PlayerBoard without action
   */
  export type PlayerBoardDefaultArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the PlayerBoard
     */
    select?: PlayerBoardSelect<ExtArgs> | null
    /**
     * Omit specific fields from the PlayerBoard
     */
    omit?: PlayerBoardOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: PlayerBoardInclude<ExtArgs> | null
  }


  /**
   * Model CompletedTile
   */

  export type AggregateCompletedTile = {
    _count: CompletedTileCountAggregateOutputType | null
    _min: CompletedTileMinAggregateOutputType | null
    _max: CompletedTileMaxAggregateOutputType | null
  }

  export type CompletedTileMinAggregateOutputType = {
    id: string | null
    playerBoardId: string | null
    tileId: string | null
    completedAt: Date | null
    completedVia: $Enums.CompletionSource | null
  }

  export type CompletedTileMaxAggregateOutputType = {
    id: string | null
    playerBoardId: string | null
    tileId: string | null
    completedAt: Date | null
    completedVia: $Enums.CompletionSource | null
  }

  export type CompletedTileCountAggregateOutputType = {
    id: number
    playerBoardId: number
    tileId: number
    completedAt: number
    completedVia: number
    _all: number
  }


  export type CompletedTileMinAggregateInputType = {
    id?: true
    playerBoardId?: true
    tileId?: true
    completedAt?: true
    completedVia?: true
  }

  export type CompletedTileMaxAggregateInputType = {
    id?: true
    playerBoardId?: true
    tileId?: true
    completedAt?: true
    completedVia?: true
  }

  export type CompletedTileCountAggregateInputType = {
    id?: true
    playerBoardId?: true
    tileId?: true
    completedAt?: true
    completedVia?: true
    _all?: true
  }

  export type CompletedTileAggregateArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Filter which CompletedTile to aggregate.
     */
    where?: CompletedTileWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of CompletedTiles to fetch.
     */
    orderBy?: CompletedTileOrderByWithRelationInput | CompletedTileOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the start position
     */
    cursor?: CompletedTileWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` CompletedTiles from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` CompletedTiles.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Count returned CompletedTiles
    **/
    _count?: true | CompletedTileCountAggregateInputType
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Select which fields to find the minimum value
    **/
    _min?: CompletedTileMinAggregateInputType
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Select which fields to find the maximum value
    **/
    _max?: CompletedTileMaxAggregateInputType
  }

  export type GetCompletedTileAggregateType<T extends CompletedTileAggregateArgs> = {
        [P in keyof T & keyof AggregateCompletedTile]: P extends '_count' | 'count'
      ? T[P] extends true
        ? number
        : GetScalarType<T[P], AggregateCompletedTile[P]>
      : GetScalarType<T[P], AggregateCompletedTile[P]>
  }




  export type CompletedTileGroupByArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    where?: CompletedTileWhereInput
    orderBy?: CompletedTileOrderByWithAggregationInput | CompletedTileOrderByWithAggregationInput[]
    by: CompletedTileScalarFieldEnum[] | CompletedTileScalarFieldEnum
    having?: CompletedTileScalarWhereWithAggregatesInput
    take?: number
    skip?: number
    _count?: CompletedTileCountAggregateInputType | true
    _min?: CompletedTileMinAggregateInputType
    _max?: CompletedTileMaxAggregateInputType
  }

  export type CompletedTileGroupByOutputType = {
    id: string
    playerBoardId: string
    tileId: string
    completedAt: Date
    completedVia: $Enums.CompletionSource
    _count: CompletedTileCountAggregateOutputType | null
    _min: CompletedTileMinAggregateOutputType | null
    _max: CompletedTileMaxAggregateOutputType | null
  }

  type GetCompletedTileGroupByPayload<T extends CompletedTileGroupByArgs> = Prisma.PrismaPromise<
    Array<
      PickEnumerable<CompletedTileGroupByOutputType, T['by']> &
        {
          [P in ((keyof T) & (keyof CompletedTileGroupByOutputType))]: P extends '_count'
            ? T[P] extends boolean
              ? number
              : GetScalarType<T[P], CompletedTileGroupByOutputType[P]>
            : GetScalarType<T[P], CompletedTileGroupByOutputType[P]>
        }
      >
    >


  export type CompletedTileSelect<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetSelect<{
    id?: boolean
    playerBoardId?: boolean
    tileId?: boolean
    completedAt?: boolean
    completedVia?: boolean
    playerBoard?: boolean | PlayerBoardDefaultArgs<ExtArgs>
    tile?: boolean | TileDefaultArgs<ExtArgs>
  }, ExtArgs["result"]["completedTile"]>

  export type CompletedTileSelectCreateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetSelect<{
    id?: boolean
    playerBoardId?: boolean
    tileId?: boolean
    completedAt?: boolean
    completedVia?: boolean
    playerBoard?: boolean | PlayerBoardDefaultArgs<ExtArgs>
    tile?: boolean | TileDefaultArgs<ExtArgs>
  }, ExtArgs["result"]["completedTile"]>

  export type CompletedTileSelectUpdateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetSelect<{
    id?: boolean
    playerBoardId?: boolean
    tileId?: boolean
    completedAt?: boolean
    completedVia?: boolean
    playerBoard?: boolean | PlayerBoardDefaultArgs<ExtArgs>
    tile?: boolean | TileDefaultArgs<ExtArgs>
  }, ExtArgs["result"]["completedTile"]>

  export type CompletedTileSelectScalar = {
    id?: boolean
    playerBoardId?: boolean
    tileId?: boolean
    completedAt?: boolean
    completedVia?: boolean
  }

  export type CompletedTileOmit<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetOmit<"id" | "playerBoardId" | "tileId" | "completedAt" | "completedVia", ExtArgs["result"]["completedTile"]>
  export type CompletedTileInclude<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    playerBoard?: boolean | PlayerBoardDefaultArgs<ExtArgs>
    tile?: boolean | TileDefaultArgs<ExtArgs>
  }
  export type CompletedTileIncludeCreateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    playerBoard?: boolean | PlayerBoardDefaultArgs<ExtArgs>
    tile?: boolean | TileDefaultArgs<ExtArgs>
  }
  export type CompletedTileIncludeUpdateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    playerBoard?: boolean | PlayerBoardDefaultArgs<ExtArgs>
    tile?: boolean | TileDefaultArgs<ExtArgs>
  }

  export type $CompletedTilePayload<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    name: "CompletedTile"
    objects: {
      playerBoard: Prisma.$PlayerBoardPayload<ExtArgs>
      tile: Prisma.$TilePayload<ExtArgs>
    }
    scalars: $Extensions.GetPayloadResult<{
      id: string
      playerBoardId: string
      tileId: string
      completedAt: Date
      completedVia: $Enums.CompletionSource
    }, ExtArgs["result"]["completedTile"]>
    composites: {}
  }

  type CompletedTileGetPayload<S extends boolean | null | undefined | CompletedTileDefaultArgs> = $Result.GetResult<Prisma.$CompletedTilePayload, S>

  type CompletedTileCountArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> =
    Omit<CompletedTileFindManyArgs, 'select' | 'include' | 'distinct' | 'omit'> & {
      select?: CompletedTileCountAggregateInputType | true
    }

  export interface CompletedTileDelegate<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs, GlobalOmitOptions = {}> {
    [K: symbol]: { types: Prisma.TypeMap<ExtArgs>['model']['CompletedTile'], meta: { name: 'CompletedTile' } }
    /**
     * Find zero or one CompletedTile that matches the filter.
     * @param {CompletedTileFindUniqueArgs} args - Arguments to find a CompletedTile
     * @example
     * // Get one CompletedTile
     * const completedTile = await prisma.completedTile.findUnique({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findUnique<T extends CompletedTileFindUniqueArgs>(args: SelectSubset<T, CompletedTileFindUniqueArgs<ExtArgs>>): Prisma__CompletedTileClient<$Result.GetResult<Prisma.$CompletedTilePayload<ExtArgs>, T, "findUnique", GlobalOmitOptions> | null, null, ExtArgs, GlobalOmitOptions>

    /**
     * Find one CompletedTile that matches the filter or throw an error with `error.code='P2025'`
     * if no matches were found.
     * @param {CompletedTileFindUniqueOrThrowArgs} args - Arguments to find a CompletedTile
     * @example
     * // Get one CompletedTile
     * const completedTile = await prisma.completedTile.findUniqueOrThrow({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findUniqueOrThrow<T extends CompletedTileFindUniqueOrThrowArgs>(args: SelectSubset<T, CompletedTileFindUniqueOrThrowArgs<ExtArgs>>): Prisma__CompletedTileClient<$Result.GetResult<Prisma.$CompletedTilePayload<ExtArgs>, T, "findUniqueOrThrow", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Find the first CompletedTile that matches the filter.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {CompletedTileFindFirstArgs} args - Arguments to find a CompletedTile
     * @example
     * // Get one CompletedTile
     * const completedTile = await prisma.completedTile.findFirst({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findFirst<T extends CompletedTileFindFirstArgs>(args?: SelectSubset<T, CompletedTileFindFirstArgs<ExtArgs>>): Prisma__CompletedTileClient<$Result.GetResult<Prisma.$CompletedTilePayload<ExtArgs>, T, "findFirst", GlobalOmitOptions> | null, null, ExtArgs, GlobalOmitOptions>

    /**
     * Find the first CompletedTile that matches the filter or
     * throw `PrismaKnownClientError` with `P2025` code if no matches were found.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {CompletedTileFindFirstOrThrowArgs} args - Arguments to find a CompletedTile
     * @example
     * // Get one CompletedTile
     * const completedTile = await prisma.completedTile.findFirstOrThrow({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findFirstOrThrow<T extends CompletedTileFindFirstOrThrowArgs>(args?: SelectSubset<T, CompletedTileFindFirstOrThrowArgs<ExtArgs>>): Prisma__CompletedTileClient<$Result.GetResult<Prisma.$CompletedTilePayload<ExtArgs>, T, "findFirstOrThrow", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Find zero or more CompletedTiles that matches the filter.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {CompletedTileFindManyArgs} args - Arguments to filter and select certain fields only.
     * @example
     * // Get all CompletedTiles
     * const completedTiles = await prisma.completedTile.findMany()
     * 
     * // Get first 10 CompletedTiles
     * const completedTiles = await prisma.completedTile.findMany({ take: 10 })
     * 
     * // Only select the `id`
     * const completedTileWithIdOnly = await prisma.completedTile.findMany({ select: { id: true } })
     * 
     */
    findMany<T extends CompletedTileFindManyArgs>(args?: SelectSubset<T, CompletedTileFindManyArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$CompletedTilePayload<ExtArgs>, T, "findMany", GlobalOmitOptions>>

    /**
     * Create a CompletedTile.
     * @param {CompletedTileCreateArgs} args - Arguments to create a CompletedTile.
     * @example
     * // Create one CompletedTile
     * const CompletedTile = await prisma.completedTile.create({
     *   data: {
     *     // ... data to create a CompletedTile
     *   }
     * })
     * 
     */
    create<T extends CompletedTileCreateArgs>(args: SelectSubset<T, CompletedTileCreateArgs<ExtArgs>>): Prisma__CompletedTileClient<$Result.GetResult<Prisma.$CompletedTilePayload<ExtArgs>, T, "create", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Create many CompletedTiles.
     * @param {CompletedTileCreateManyArgs} args - Arguments to create many CompletedTiles.
     * @example
     * // Create many CompletedTiles
     * const completedTile = await prisma.completedTile.createMany({
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     *     
     */
    createMany<T extends CompletedTileCreateManyArgs>(args?: SelectSubset<T, CompletedTileCreateManyArgs<ExtArgs>>): Prisma.PrismaPromise<BatchPayload>

    /**
     * Create many CompletedTiles and returns the data saved in the database.
     * @param {CompletedTileCreateManyAndReturnArgs} args - Arguments to create many CompletedTiles.
     * @example
     * // Create many CompletedTiles
     * const completedTile = await prisma.completedTile.createManyAndReturn({
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * 
     * // Create many CompletedTiles and only return the `id`
     * const completedTileWithIdOnly = await prisma.completedTile.createManyAndReturn({
     *   select: { id: true },
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * 
     */
    createManyAndReturn<T extends CompletedTileCreateManyAndReturnArgs>(args?: SelectSubset<T, CompletedTileCreateManyAndReturnArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$CompletedTilePayload<ExtArgs>, T, "createManyAndReturn", GlobalOmitOptions>>

    /**
     * Delete a CompletedTile.
     * @param {CompletedTileDeleteArgs} args - Arguments to delete one CompletedTile.
     * @example
     * // Delete one CompletedTile
     * const CompletedTile = await prisma.completedTile.delete({
     *   where: {
     *     // ... filter to delete one CompletedTile
     *   }
     * })
     * 
     */
    delete<T extends CompletedTileDeleteArgs>(args: SelectSubset<T, CompletedTileDeleteArgs<ExtArgs>>): Prisma__CompletedTileClient<$Result.GetResult<Prisma.$CompletedTilePayload<ExtArgs>, T, "delete", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Update one CompletedTile.
     * @param {CompletedTileUpdateArgs} args - Arguments to update one CompletedTile.
     * @example
     * // Update one CompletedTile
     * const completedTile = await prisma.completedTile.update({
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: {
     *     // ... provide data here
     *   }
     * })
     * 
     */
    update<T extends CompletedTileUpdateArgs>(args: SelectSubset<T, CompletedTileUpdateArgs<ExtArgs>>): Prisma__CompletedTileClient<$Result.GetResult<Prisma.$CompletedTilePayload<ExtArgs>, T, "update", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Delete zero or more CompletedTiles.
     * @param {CompletedTileDeleteManyArgs} args - Arguments to filter CompletedTiles to delete.
     * @example
     * // Delete a few CompletedTiles
     * const { count } = await prisma.completedTile.deleteMany({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     * 
     */
    deleteMany<T extends CompletedTileDeleteManyArgs>(args?: SelectSubset<T, CompletedTileDeleteManyArgs<ExtArgs>>): Prisma.PrismaPromise<BatchPayload>

    /**
     * Update zero or more CompletedTiles.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {CompletedTileUpdateManyArgs} args - Arguments to update one or more rows.
     * @example
     * // Update many CompletedTiles
     * const completedTile = await prisma.completedTile.updateMany({
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: {
     *     // ... provide data here
     *   }
     * })
     * 
     */
    updateMany<T extends CompletedTileUpdateManyArgs>(args: SelectSubset<T, CompletedTileUpdateManyArgs<ExtArgs>>): Prisma.PrismaPromise<BatchPayload>

    /**
     * Update zero or more CompletedTiles and returns the data updated in the database.
     * @param {CompletedTileUpdateManyAndReturnArgs} args - Arguments to update many CompletedTiles.
     * @example
     * // Update many CompletedTiles
     * const completedTile = await prisma.completedTile.updateManyAndReturn({
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * 
     * // Update zero or more CompletedTiles and only return the `id`
     * const completedTileWithIdOnly = await prisma.completedTile.updateManyAndReturn({
     *   select: { id: true },
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * 
     */
    updateManyAndReturn<T extends CompletedTileUpdateManyAndReturnArgs>(args: SelectSubset<T, CompletedTileUpdateManyAndReturnArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$CompletedTilePayload<ExtArgs>, T, "updateManyAndReturn", GlobalOmitOptions>>

    /**
     * Create or update one CompletedTile.
     * @param {CompletedTileUpsertArgs} args - Arguments to update or create a CompletedTile.
     * @example
     * // Update or create a CompletedTile
     * const completedTile = await prisma.completedTile.upsert({
     *   create: {
     *     // ... data to create a CompletedTile
     *   },
     *   update: {
     *     // ... in case it already exists, update
     *   },
     *   where: {
     *     // ... the filter for the CompletedTile we want to update
     *   }
     * })
     */
    upsert<T extends CompletedTileUpsertArgs>(args: SelectSubset<T, CompletedTileUpsertArgs<ExtArgs>>): Prisma__CompletedTileClient<$Result.GetResult<Prisma.$CompletedTilePayload<ExtArgs>, T, "upsert", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>


    /**
     * Count the number of CompletedTiles.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {CompletedTileCountArgs} args - Arguments to filter CompletedTiles to count.
     * @example
     * // Count the number of CompletedTiles
     * const count = await prisma.completedTile.count({
     *   where: {
     *     // ... the filter for the CompletedTiles we want to count
     *   }
     * })
    **/
    count<T extends CompletedTileCountArgs>(
      args?: Subset<T, CompletedTileCountArgs>,
    ): Prisma.PrismaPromise<
      T extends $Utils.Record<'select', any>
        ? T['select'] extends true
          ? number
          : GetScalarType<T['select'], CompletedTileCountAggregateOutputType>
        : number
    >

    /**
     * Allows you to perform aggregations operations on a CompletedTile.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {CompletedTileAggregateArgs} args - Select which aggregations you would like to apply and on what fields.
     * @example
     * // Ordered by age ascending
     * // Where email contains prisma.io
     * // Limited to the 10 users
     * const aggregations = await prisma.user.aggregate({
     *   _avg: {
     *     age: true,
     *   },
     *   where: {
     *     email: {
     *       contains: "prisma.io",
     *     },
     *   },
     *   orderBy: {
     *     age: "asc",
     *   },
     *   take: 10,
     * })
    **/
    aggregate<T extends CompletedTileAggregateArgs>(args: Subset<T, CompletedTileAggregateArgs>): Prisma.PrismaPromise<GetCompletedTileAggregateType<T>>

    /**
     * Group by CompletedTile.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {CompletedTileGroupByArgs} args - Group by arguments.
     * @example
     * // Group by city, order by createdAt, get count
     * const result = await prisma.user.groupBy({
     *   by: ['city', 'createdAt'],
     *   orderBy: {
     *     createdAt: true
     *   },
     *   _count: {
     *     _all: true
     *   },
     * })
     * 
    **/
    groupBy<
      T extends CompletedTileGroupByArgs,
      HasSelectOrTake extends Or<
        Extends<'skip', Keys<T>>,
        Extends<'take', Keys<T>>
      >,
      OrderByArg extends True extends HasSelectOrTake
        ? { orderBy: CompletedTileGroupByArgs['orderBy'] }
        : { orderBy?: CompletedTileGroupByArgs['orderBy'] },
      OrderFields extends ExcludeUnderscoreKeys<Keys<MaybeTupleToUnion<T['orderBy']>>>,
      ByFields extends MaybeTupleToUnion<T['by']>,
      ByValid extends Has<ByFields, OrderFields>,
      HavingFields extends GetHavingFields<T['having']>,
      HavingValid extends Has<ByFields, HavingFields>,
      ByEmpty extends T['by'] extends never[] ? True : False,
      InputErrors extends ByEmpty extends True
      ? `Error: "by" must not be empty.`
      : HavingValid extends False
      ? {
          [P in HavingFields]: P extends ByFields
            ? never
            : P extends string
            ? `Error: Field "${P}" used in "having" needs to be provided in "by".`
            : [
                Error,
                'Field ',
                P,
                ` in "having" needs to be provided in "by"`,
              ]
        }[HavingFields]
      : 'take' extends Keys<T>
      ? 'orderBy' extends Keys<T>
        ? ByValid extends True
          ? {}
          : {
              [P in OrderFields]: P extends ByFields
                ? never
                : `Error: Field "${P}" in "orderBy" needs to be provided in "by"`
            }[OrderFields]
        : 'Error: If you provide "take", you also need to provide "orderBy"'
      : 'skip' extends Keys<T>
      ? 'orderBy' extends Keys<T>
        ? ByValid extends True
          ? {}
          : {
              [P in OrderFields]: P extends ByFields
                ? never
                : `Error: Field "${P}" in "orderBy" needs to be provided in "by"`
            }[OrderFields]
        : 'Error: If you provide "skip", you also need to provide "orderBy"'
      : ByValid extends True
      ? {}
      : {
          [P in OrderFields]: P extends ByFields
            ? never
            : `Error: Field "${P}" in "orderBy" needs to be provided in "by"`
        }[OrderFields]
    >(args: SubsetIntersection<T, CompletedTileGroupByArgs, OrderByArg> & InputErrors): {} extends InputErrors ? GetCompletedTileGroupByPayload<T> : Prisma.PrismaPromise<InputErrors>
  /**
   * Fields of the CompletedTile model
   */
  readonly fields: CompletedTileFieldRefs;
  }

  /**
   * The delegate class that acts as a "Promise-like" for CompletedTile.
   * Why is this prefixed with `Prisma__`?
   * Because we want to prevent naming conflicts as mentioned in
   * https://github.com/prisma/prisma-client-js/issues/707
   */
  export interface Prisma__CompletedTileClient<T, Null = never, ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs, GlobalOmitOptions = {}> extends Prisma.PrismaPromise<T> {
    readonly [Symbol.toStringTag]: "PrismaPromise"
    playerBoard<T extends PlayerBoardDefaultArgs<ExtArgs> = {}>(args?: Subset<T, PlayerBoardDefaultArgs<ExtArgs>>): Prisma__PlayerBoardClient<$Result.GetResult<Prisma.$PlayerBoardPayload<ExtArgs>, T, "findUniqueOrThrow", GlobalOmitOptions> | Null, Null, ExtArgs, GlobalOmitOptions>
    tile<T extends TileDefaultArgs<ExtArgs> = {}>(args?: Subset<T, TileDefaultArgs<ExtArgs>>): Prisma__TileClient<$Result.GetResult<Prisma.$TilePayload<ExtArgs>, T, "findUniqueOrThrow", GlobalOmitOptions> | Null, Null, ExtArgs, GlobalOmitOptions>
    /**
     * Attaches callbacks for the resolution and/or rejection of the Promise.
     * @param onfulfilled The callback to execute when the Promise is resolved.
     * @param onrejected The callback to execute when the Promise is rejected.
     * @returns A Promise for the completion of which ever callback is executed.
     */
    then<TResult1 = T, TResult2 = never>(onfulfilled?: ((value: T) => TResult1 | PromiseLike<TResult1>) | undefined | null, onrejected?: ((reason: any) => TResult2 | PromiseLike<TResult2>) | undefined | null): $Utils.JsPromise<TResult1 | TResult2>
    /**
     * Attaches a callback for only the rejection of the Promise.
     * @param onrejected The callback to execute when the Promise is rejected.
     * @returns A Promise for the completion of the callback.
     */
    catch<TResult = never>(onrejected?: ((reason: any) => TResult | PromiseLike<TResult>) | undefined | null): $Utils.JsPromise<T | TResult>
    /**
     * Attaches a callback that is invoked when the Promise is settled (fulfilled or rejected). The
     * resolved value cannot be modified from the callback.
     * @param onfinally The callback to execute when the Promise is settled (fulfilled or rejected).
     * @returns A Promise for the completion of the callback.
     */
    finally(onfinally?: (() => void) | undefined | null): $Utils.JsPromise<T>
  }




  /**
   * Fields of the CompletedTile model
   */
  interface CompletedTileFieldRefs {
    readonly id: FieldRef<"CompletedTile", 'String'>
    readonly playerBoardId: FieldRef<"CompletedTile", 'String'>
    readonly tileId: FieldRef<"CompletedTile", 'String'>
    readonly completedAt: FieldRef<"CompletedTile", 'DateTime'>
    readonly completedVia: FieldRef<"CompletedTile", 'CompletionSource'>
  }
    

  // Custom InputTypes
  /**
   * CompletedTile findUnique
   */
  export type CompletedTileFindUniqueArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the CompletedTile
     */
    select?: CompletedTileSelect<ExtArgs> | null
    /**
     * Omit specific fields from the CompletedTile
     */
    omit?: CompletedTileOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: CompletedTileInclude<ExtArgs> | null
    /**
     * Filter, which CompletedTile to fetch.
     */
    where: CompletedTileWhereUniqueInput
  }

  /**
   * CompletedTile findUniqueOrThrow
   */
  export type CompletedTileFindUniqueOrThrowArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the CompletedTile
     */
    select?: CompletedTileSelect<ExtArgs> | null
    /**
     * Omit specific fields from the CompletedTile
     */
    omit?: CompletedTileOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: CompletedTileInclude<ExtArgs> | null
    /**
     * Filter, which CompletedTile to fetch.
     */
    where: CompletedTileWhereUniqueInput
  }

  /**
   * CompletedTile findFirst
   */
  export type CompletedTileFindFirstArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the CompletedTile
     */
    select?: CompletedTileSelect<ExtArgs> | null
    /**
     * Omit specific fields from the CompletedTile
     */
    omit?: CompletedTileOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: CompletedTileInclude<ExtArgs> | null
    /**
     * Filter, which CompletedTile to fetch.
     */
    where?: CompletedTileWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of CompletedTiles to fetch.
     */
    orderBy?: CompletedTileOrderByWithRelationInput | CompletedTileOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the position for searching for CompletedTiles.
     */
    cursor?: CompletedTileWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` CompletedTiles from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` CompletedTiles.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/distinct Distinct Docs}
     * 
     * Filter by unique combinations of CompletedTiles.
     */
    distinct?: CompletedTileScalarFieldEnum | CompletedTileScalarFieldEnum[]
  }

  /**
   * CompletedTile findFirstOrThrow
   */
  export type CompletedTileFindFirstOrThrowArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the CompletedTile
     */
    select?: CompletedTileSelect<ExtArgs> | null
    /**
     * Omit specific fields from the CompletedTile
     */
    omit?: CompletedTileOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: CompletedTileInclude<ExtArgs> | null
    /**
     * Filter, which CompletedTile to fetch.
     */
    where?: CompletedTileWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of CompletedTiles to fetch.
     */
    orderBy?: CompletedTileOrderByWithRelationInput | CompletedTileOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the position for searching for CompletedTiles.
     */
    cursor?: CompletedTileWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` CompletedTiles from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` CompletedTiles.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/distinct Distinct Docs}
     * 
     * Filter by unique combinations of CompletedTiles.
     */
    distinct?: CompletedTileScalarFieldEnum | CompletedTileScalarFieldEnum[]
  }

  /**
   * CompletedTile findMany
   */
  export type CompletedTileFindManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the CompletedTile
     */
    select?: CompletedTileSelect<ExtArgs> | null
    /**
     * Omit specific fields from the CompletedTile
     */
    omit?: CompletedTileOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: CompletedTileInclude<ExtArgs> | null
    /**
     * Filter, which CompletedTiles to fetch.
     */
    where?: CompletedTileWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of CompletedTiles to fetch.
     */
    orderBy?: CompletedTileOrderByWithRelationInput | CompletedTileOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the position for listing CompletedTiles.
     */
    cursor?: CompletedTileWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` CompletedTiles from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` CompletedTiles.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/distinct Distinct Docs}
     * 
     * Filter by unique combinations of CompletedTiles.
     */
    distinct?: CompletedTileScalarFieldEnum | CompletedTileScalarFieldEnum[]
  }

  /**
   * CompletedTile create
   */
  export type CompletedTileCreateArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the CompletedTile
     */
    select?: CompletedTileSelect<ExtArgs> | null
    /**
     * Omit specific fields from the CompletedTile
     */
    omit?: CompletedTileOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: CompletedTileInclude<ExtArgs> | null
    /**
     * The data needed to create a CompletedTile.
     */
    data: XOR<CompletedTileCreateInput, CompletedTileUncheckedCreateInput>
  }

  /**
   * CompletedTile createMany
   */
  export type CompletedTileCreateManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * The data used to create many CompletedTiles.
     */
    data: CompletedTileCreateManyInput | CompletedTileCreateManyInput[]
    skipDuplicates?: boolean
  }

  /**
   * CompletedTile createManyAndReturn
   */
  export type CompletedTileCreateManyAndReturnArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the CompletedTile
     */
    select?: CompletedTileSelectCreateManyAndReturn<ExtArgs> | null
    /**
     * Omit specific fields from the CompletedTile
     */
    omit?: CompletedTileOmit<ExtArgs> | null
    /**
     * The data used to create many CompletedTiles.
     */
    data: CompletedTileCreateManyInput | CompletedTileCreateManyInput[]
    skipDuplicates?: boolean
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: CompletedTileIncludeCreateManyAndReturn<ExtArgs> | null
  }

  /**
   * CompletedTile update
   */
  export type CompletedTileUpdateArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the CompletedTile
     */
    select?: CompletedTileSelect<ExtArgs> | null
    /**
     * Omit specific fields from the CompletedTile
     */
    omit?: CompletedTileOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: CompletedTileInclude<ExtArgs> | null
    /**
     * The data needed to update a CompletedTile.
     */
    data: XOR<CompletedTileUpdateInput, CompletedTileUncheckedUpdateInput>
    /**
     * Choose, which CompletedTile to update.
     */
    where: CompletedTileWhereUniqueInput
  }

  /**
   * CompletedTile updateMany
   */
  export type CompletedTileUpdateManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * The data used to update CompletedTiles.
     */
    data: XOR<CompletedTileUpdateManyMutationInput, CompletedTileUncheckedUpdateManyInput>
    /**
     * Filter which CompletedTiles to update
     */
    where?: CompletedTileWhereInput
    /**
     * Limit how many CompletedTiles to update.
     */
    limit?: number
  }

  /**
   * CompletedTile updateManyAndReturn
   */
  export type CompletedTileUpdateManyAndReturnArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the CompletedTile
     */
    select?: CompletedTileSelectUpdateManyAndReturn<ExtArgs> | null
    /**
     * Omit specific fields from the CompletedTile
     */
    omit?: CompletedTileOmit<ExtArgs> | null
    /**
     * The data used to update CompletedTiles.
     */
    data: XOR<CompletedTileUpdateManyMutationInput, CompletedTileUncheckedUpdateManyInput>
    /**
     * Filter which CompletedTiles to update
     */
    where?: CompletedTileWhereInput
    /**
     * Limit how many CompletedTiles to update.
     */
    limit?: number
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: CompletedTileIncludeUpdateManyAndReturn<ExtArgs> | null
  }

  /**
   * CompletedTile upsert
   */
  export type CompletedTileUpsertArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the CompletedTile
     */
    select?: CompletedTileSelect<ExtArgs> | null
    /**
     * Omit specific fields from the CompletedTile
     */
    omit?: CompletedTileOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: CompletedTileInclude<ExtArgs> | null
    /**
     * The filter to search for the CompletedTile to update in case it exists.
     */
    where: CompletedTileWhereUniqueInput
    /**
     * In case the CompletedTile found by the `where` argument doesn't exist, create a new CompletedTile with this data.
     */
    create: XOR<CompletedTileCreateInput, CompletedTileUncheckedCreateInput>
    /**
     * In case the CompletedTile was found with the provided `where` argument, update it with this data.
     */
    update: XOR<CompletedTileUpdateInput, CompletedTileUncheckedUpdateInput>
  }

  /**
   * CompletedTile delete
   */
  export type CompletedTileDeleteArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the CompletedTile
     */
    select?: CompletedTileSelect<ExtArgs> | null
    /**
     * Omit specific fields from the CompletedTile
     */
    omit?: CompletedTileOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: CompletedTileInclude<ExtArgs> | null
    /**
     * Filter which CompletedTile to delete.
     */
    where: CompletedTileWhereUniqueInput
  }

  /**
   * CompletedTile deleteMany
   */
  export type CompletedTileDeleteManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Filter which CompletedTiles to delete
     */
    where?: CompletedTileWhereInput
    /**
     * Limit how many CompletedTiles to delete.
     */
    limit?: number
  }

  /**
   * CompletedTile without action
   */
  export type CompletedTileDefaultArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the CompletedTile
     */
    select?: CompletedTileSelect<ExtArgs> | null
    /**
     * Omit specific fields from the CompletedTile
     */
    omit?: CompletedTileOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: CompletedTileInclude<ExtArgs> | null
  }


  /**
   * Model Team
   */

  export type AggregateTeam = {
    _count: TeamCountAggregateOutputType | null
    _min: TeamMinAggregateOutputType | null
    _max: TeamMaxAggregateOutputType | null
  }

  export type TeamMinAggregateOutputType = {
    id: string | null
    name: string | null
    iconUrl: string | null
    createdAt: Date | null
    updatedAt: Date | null
  }

  export type TeamMaxAggregateOutputType = {
    id: string | null
    name: string | null
    iconUrl: string | null
    createdAt: Date | null
    updatedAt: Date | null
  }

  export type TeamCountAggregateOutputType = {
    id: number
    name: number
    iconUrl: number
    createdAt: number
    updatedAt: number
    _all: number
  }


  export type TeamMinAggregateInputType = {
    id?: true
    name?: true
    iconUrl?: true
    createdAt?: true
    updatedAt?: true
  }

  export type TeamMaxAggregateInputType = {
    id?: true
    name?: true
    iconUrl?: true
    createdAt?: true
    updatedAt?: true
  }

  export type TeamCountAggregateInputType = {
    id?: true
    name?: true
    iconUrl?: true
    createdAt?: true
    updatedAt?: true
    _all?: true
  }

  export type TeamAggregateArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Filter which Team to aggregate.
     */
    where?: TeamWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of Teams to fetch.
     */
    orderBy?: TeamOrderByWithRelationInput | TeamOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the start position
     */
    cursor?: TeamWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` Teams from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` Teams.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Count returned Teams
    **/
    _count?: true | TeamCountAggregateInputType
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Select which fields to find the minimum value
    **/
    _min?: TeamMinAggregateInputType
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Select which fields to find the maximum value
    **/
    _max?: TeamMaxAggregateInputType
  }

  export type GetTeamAggregateType<T extends TeamAggregateArgs> = {
        [P in keyof T & keyof AggregateTeam]: P extends '_count' | 'count'
      ? T[P] extends true
        ? number
        : GetScalarType<T[P], AggregateTeam[P]>
      : GetScalarType<T[P], AggregateTeam[P]>
  }




  export type TeamGroupByArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    where?: TeamWhereInput
    orderBy?: TeamOrderByWithAggregationInput | TeamOrderByWithAggregationInput[]
    by: TeamScalarFieldEnum[] | TeamScalarFieldEnum
    having?: TeamScalarWhereWithAggregatesInput
    take?: number
    skip?: number
    _count?: TeamCountAggregateInputType | true
    _min?: TeamMinAggregateInputType
    _max?: TeamMaxAggregateInputType
  }

  export type TeamGroupByOutputType = {
    id: string
    name: string
    iconUrl: string | null
    createdAt: Date
    updatedAt: Date
    _count: TeamCountAggregateOutputType | null
    _min: TeamMinAggregateOutputType | null
    _max: TeamMaxAggregateOutputType | null
  }

  type GetTeamGroupByPayload<T extends TeamGroupByArgs> = Prisma.PrismaPromise<
    Array<
      PickEnumerable<TeamGroupByOutputType, T['by']> &
        {
          [P in ((keyof T) & (keyof TeamGroupByOutputType))]: P extends '_count'
            ? T[P] extends boolean
              ? number
              : GetScalarType<T[P], TeamGroupByOutputType[P]>
            : GetScalarType<T[P], TeamGroupByOutputType[P]>
        }
      >
    >


  export type TeamSelect<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetSelect<{
    id?: boolean
    name?: boolean
    iconUrl?: boolean
    createdAt?: boolean
    updatedAt?: boolean
    members?: boolean | Team$membersArgs<ExtArgs>
    boardTeams?: boolean | Team$boardTeamsArgs<ExtArgs>
    playerBoards?: boolean | Team$playerBoardsArgs<ExtArgs>
    _count?: boolean | TeamCountOutputTypeDefaultArgs<ExtArgs>
  }, ExtArgs["result"]["team"]>

  export type TeamSelectCreateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetSelect<{
    id?: boolean
    name?: boolean
    iconUrl?: boolean
    createdAt?: boolean
    updatedAt?: boolean
  }, ExtArgs["result"]["team"]>

  export type TeamSelectUpdateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetSelect<{
    id?: boolean
    name?: boolean
    iconUrl?: boolean
    createdAt?: boolean
    updatedAt?: boolean
  }, ExtArgs["result"]["team"]>

  export type TeamSelectScalar = {
    id?: boolean
    name?: boolean
    iconUrl?: boolean
    createdAt?: boolean
    updatedAt?: boolean
  }

  export type TeamOmit<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetOmit<"id" | "name" | "iconUrl" | "createdAt" | "updatedAt", ExtArgs["result"]["team"]>
  export type TeamInclude<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    members?: boolean | Team$membersArgs<ExtArgs>
    boardTeams?: boolean | Team$boardTeamsArgs<ExtArgs>
    playerBoards?: boolean | Team$playerBoardsArgs<ExtArgs>
    _count?: boolean | TeamCountOutputTypeDefaultArgs<ExtArgs>
  }
  export type TeamIncludeCreateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {}
  export type TeamIncludeUpdateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {}

  export type $TeamPayload<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    name: "Team"
    objects: {
      members: Prisma.$TeamMemberPayload<ExtArgs>[]
      boardTeams: Prisma.$BoardTeamPayload<ExtArgs>[]
      playerBoards: Prisma.$PlayerBoardPayload<ExtArgs>[]
    }
    scalars: $Extensions.GetPayloadResult<{
      id: string
      name: string
      iconUrl: string | null
      createdAt: Date
      updatedAt: Date
    }, ExtArgs["result"]["team"]>
    composites: {}
  }

  type TeamGetPayload<S extends boolean | null | undefined | TeamDefaultArgs> = $Result.GetResult<Prisma.$TeamPayload, S>

  type TeamCountArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> =
    Omit<TeamFindManyArgs, 'select' | 'include' | 'distinct' | 'omit'> & {
      select?: TeamCountAggregateInputType | true
    }

  export interface TeamDelegate<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs, GlobalOmitOptions = {}> {
    [K: symbol]: { types: Prisma.TypeMap<ExtArgs>['model']['Team'], meta: { name: 'Team' } }
    /**
     * Find zero or one Team that matches the filter.
     * @param {TeamFindUniqueArgs} args - Arguments to find a Team
     * @example
     * // Get one Team
     * const team = await prisma.team.findUnique({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findUnique<T extends TeamFindUniqueArgs>(args: SelectSubset<T, TeamFindUniqueArgs<ExtArgs>>): Prisma__TeamClient<$Result.GetResult<Prisma.$TeamPayload<ExtArgs>, T, "findUnique", GlobalOmitOptions> | null, null, ExtArgs, GlobalOmitOptions>

    /**
     * Find one Team that matches the filter or throw an error with `error.code='P2025'`
     * if no matches were found.
     * @param {TeamFindUniqueOrThrowArgs} args - Arguments to find a Team
     * @example
     * // Get one Team
     * const team = await prisma.team.findUniqueOrThrow({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findUniqueOrThrow<T extends TeamFindUniqueOrThrowArgs>(args: SelectSubset<T, TeamFindUniqueOrThrowArgs<ExtArgs>>): Prisma__TeamClient<$Result.GetResult<Prisma.$TeamPayload<ExtArgs>, T, "findUniqueOrThrow", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Find the first Team that matches the filter.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {TeamFindFirstArgs} args - Arguments to find a Team
     * @example
     * // Get one Team
     * const team = await prisma.team.findFirst({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findFirst<T extends TeamFindFirstArgs>(args?: SelectSubset<T, TeamFindFirstArgs<ExtArgs>>): Prisma__TeamClient<$Result.GetResult<Prisma.$TeamPayload<ExtArgs>, T, "findFirst", GlobalOmitOptions> | null, null, ExtArgs, GlobalOmitOptions>

    /**
     * Find the first Team that matches the filter or
     * throw `PrismaKnownClientError` with `P2025` code if no matches were found.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {TeamFindFirstOrThrowArgs} args - Arguments to find a Team
     * @example
     * // Get one Team
     * const team = await prisma.team.findFirstOrThrow({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findFirstOrThrow<T extends TeamFindFirstOrThrowArgs>(args?: SelectSubset<T, TeamFindFirstOrThrowArgs<ExtArgs>>): Prisma__TeamClient<$Result.GetResult<Prisma.$TeamPayload<ExtArgs>, T, "findFirstOrThrow", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Find zero or more Teams that matches the filter.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {TeamFindManyArgs} args - Arguments to filter and select certain fields only.
     * @example
     * // Get all Teams
     * const teams = await prisma.team.findMany()
     * 
     * // Get first 10 Teams
     * const teams = await prisma.team.findMany({ take: 10 })
     * 
     * // Only select the `id`
     * const teamWithIdOnly = await prisma.team.findMany({ select: { id: true } })
     * 
     */
    findMany<T extends TeamFindManyArgs>(args?: SelectSubset<T, TeamFindManyArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$TeamPayload<ExtArgs>, T, "findMany", GlobalOmitOptions>>

    /**
     * Create a Team.
     * @param {TeamCreateArgs} args - Arguments to create a Team.
     * @example
     * // Create one Team
     * const Team = await prisma.team.create({
     *   data: {
     *     // ... data to create a Team
     *   }
     * })
     * 
     */
    create<T extends TeamCreateArgs>(args: SelectSubset<T, TeamCreateArgs<ExtArgs>>): Prisma__TeamClient<$Result.GetResult<Prisma.$TeamPayload<ExtArgs>, T, "create", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Create many Teams.
     * @param {TeamCreateManyArgs} args - Arguments to create many Teams.
     * @example
     * // Create many Teams
     * const team = await prisma.team.createMany({
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     *     
     */
    createMany<T extends TeamCreateManyArgs>(args?: SelectSubset<T, TeamCreateManyArgs<ExtArgs>>): Prisma.PrismaPromise<BatchPayload>

    /**
     * Create many Teams and returns the data saved in the database.
     * @param {TeamCreateManyAndReturnArgs} args - Arguments to create many Teams.
     * @example
     * // Create many Teams
     * const team = await prisma.team.createManyAndReturn({
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * 
     * // Create many Teams and only return the `id`
     * const teamWithIdOnly = await prisma.team.createManyAndReturn({
     *   select: { id: true },
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * 
     */
    createManyAndReturn<T extends TeamCreateManyAndReturnArgs>(args?: SelectSubset<T, TeamCreateManyAndReturnArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$TeamPayload<ExtArgs>, T, "createManyAndReturn", GlobalOmitOptions>>

    /**
     * Delete a Team.
     * @param {TeamDeleteArgs} args - Arguments to delete one Team.
     * @example
     * // Delete one Team
     * const Team = await prisma.team.delete({
     *   where: {
     *     // ... filter to delete one Team
     *   }
     * })
     * 
     */
    delete<T extends TeamDeleteArgs>(args: SelectSubset<T, TeamDeleteArgs<ExtArgs>>): Prisma__TeamClient<$Result.GetResult<Prisma.$TeamPayload<ExtArgs>, T, "delete", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Update one Team.
     * @param {TeamUpdateArgs} args - Arguments to update one Team.
     * @example
     * // Update one Team
     * const team = await prisma.team.update({
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: {
     *     // ... provide data here
     *   }
     * })
     * 
     */
    update<T extends TeamUpdateArgs>(args: SelectSubset<T, TeamUpdateArgs<ExtArgs>>): Prisma__TeamClient<$Result.GetResult<Prisma.$TeamPayload<ExtArgs>, T, "update", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Delete zero or more Teams.
     * @param {TeamDeleteManyArgs} args - Arguments to filter Teams to delete.
     * @example
     * // Delete a few Teams
     * const { count } = await prisma.team.deleteMany({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     * 
     */
    deleteMany<T extends TeamDeleteManyArgs>(args?: SelectSubset<T, TeamDeleteManyArgs<ExtArgs>>): Prisma.PrismaPromise<BatchPayload>

    /**
     * Update zero or more Teams.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {TeamUpdateManyArgs} args - Arguments to update one or more rows.
     * @example
     * // Update many Teams
     * const team = await prisma.team.updateMany({
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: {
     *     // ... provide data here
     *   }
     * })
     * 
     */
    updateMany<T extends TeamUpdateManyArgs>(args: SelectSubset<T, TeamUpdateManyArgs<ExtArgs>>): Prisma.PrismaPromise<BatchPayload>

    /**
     * Update zero or more Teams and returns the data updated in the database.
     * @param {TeamUpdateManyAndReturnArgs} args - Arguments to update many Teams.
     * @example
     * // Update many Teams
     * const team = await prisma.team.updateManyAndReturn({
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * 
     * // Update zero or more Teams and only return the `id`
     * const teamWithIdOnly = await prisma.team.updateManyAndReturn({
     *   select: { id: true },
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * 
     */
    updateManyAndReturn<T extends TeamUpdateManyAndReturnArgs>(args: SelectSubset<T, TeamUpdateManyAndReturnArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$TeamPayload<ExtArgs>, T, "updateManyAndReturn", GlobalOmitOptions>>

    /**
     * Create or update one Team.
     * @param {TeamUpsertArgs} args - Arguments to update or create a Team.
     * @example
     * // Update or create a Team
     * const team = await prisma.team.upsert({
     *   create: {
     *     // ... data to create a Team
     *   },
     *   update: {
     *     // ... in case it already exists, update
     *   },
     *   where: {
     *     // ... the filter for the Team we want to update
     *   }
     * })
     */
    upsert<T extends TeamUpsertArgs>(args: SelectSubset<T, TeamUpsertArgs<ExtArgs>>): Prisma__TeamClient<$Result.GetResult<Prisma.$TeamPayload<ExtArgs>, T, "upsert", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>


    /**
     * Count the number of Teams.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {TeamCountArgs} args - Arguments to filter Teams to count.
     * @example
     * // Count the number of Teams
     * const count = await prisma.team.count({
     *   where: {
     *     // ... the filter for the Teams we want to count
     *   }
     * })
    **/
    count<T extends TeamCountArgs>(
      args?: Subset<T, TeamCountArgs>,
    ): Prisma.PrismaPromise<
      T extends $Utils.Record<'select', any>
        ? T['select'] extends true
          ? number
          : GetScalarType<T['select'], TeamCountAggregateOutputType>
        : number
    >

    /**
     * Allows you to perform aggregations operations on a Team.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {TeamAggregateArgs} args - Select which aggregations you would like to apply and on what fields.
     * @example
     * // Ordered by age ascending
     * // Where email contains prisma.io
     * // Limited to the 10 users
     * const aggregations = await prisma.user.aggregate({
     *   _avg: {
     *     age: true,
     *   },
     *   where: {
     *     email: {
     *       contains: "prisma.io",
     *     },
     *   },
     *   orderBy: {
     *     age: "asc",
     *   },
     *   take: 10,
     * })
    **/
    aggregate<T extends TeamAggregateArgs>(args: Subset<T, TeamAggregateArgs>): Prisma.PrismaPromise<GetTeamAggregateType<T>>

    /**
     * Group by Team.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {TeamGroupByArgs} args - Group by arguments.
     * @example
     * // Group by city, order by createdAt, get count
     * const result = await prisma.user.groupBy({
     *   by: ['city', 'createdAt'],
     *   orderBy: {
     *     createdAt: true
     *   },
     *   _count: {
     *     _all: true
     *   },
     * })
     * 
    **/
    groupBy<
      T extends TeamGroupByArgs,
      HasSelectOrTake extends Or<
        Extends<'skip', Keys<T>>,
        Extends<'take', Keys<T>>
      >,
      OrderByArg extends True extends HasSelectOrTake
        ? { orderBy: TeamGroupByArgs['orderBy'] }
        : { orderBy?: TeamGroupByArgs['orderBy'] },
      OrderFields extends ExcludeUnderscoreKeys<Keys<MaybeTupleToUnion<T['orderBy']>>>,
      ByFields extends MaybeTupleToUnion<T['by']>,
      ByValid extends Has<ByFields, OrderFields>,
      HavingFields extends GetHavingFields<T['having']>,
      HavingValid extends Has<ByFields, HavingFields>,
      ByEmpty extends T['by'] extends never[] ? True : False,
      InputErrors extends ByEmpty extends True
      ? `Error: "by" must not be empty.`
      : HavingValid extends False
      ? {
          [P in HavingFields]: P extends ByFields
            ? never
            : P extends string
            ? `Error: Field "${P}" used in "having" needs to be provided in "by".`
            : [
                Error,
                'Field ',
                P,
                ` in "having" needs to be provided in "by"`,
              ]
        }[HavingFields]
      : 'take' extends Keys<T>
      ? 'orderBy' extends Keys<T>
        ? ByValid extends True
          ? {}
          : {
              [P in OrderFields]: P extends ByFields
                ? never
                : `Error: Field "${P}" in "orderBy" needs to be provided in "by"`
            }[OrderFields]
        : 'Error: If you provide "take", you also need to provide "orderBy"'
      : 'skip' extends Keys<T>
      ? 'orderBy' extends Keys<T>
        ? ByValid extends True
          ? {}
          : {
              [P in OrderFields]: P extends ByFields
                ? never
                : `Error: Field "${P}" in "orderBy" needs to be provided in "by"`
            }[OrderFields]
        : 'Error: If you provide "skip", you also need to provide "orderBy"'
      : ByValid extends True
      ? {}
      : {
          [P in OrderFields]: P extends ByFields
            ? never
            : `Error: Field "${P}" in "orderBy" needs to be provided in "by"`
        }[OrderFields]
    >(args: SubsetIntersection<T, TeamGroupByArgs, OrderByArg> & InputErrors): {} extends InputErrors ? GetTeamGroupByPayload<T> : Prisma.PrismaPromise<InputErrors>
  /**
   * Fields of the Team model
   */
  readonly fields: TeamFieldRefs;
  }

  /**
   * The delegate class that acts as a "Promise-like" for Team.
   * Why is this prefixed with `Prisma__`?
   * Because we want to prevent naming conflicts as mentioned in
   * https://github.com/prisma/prisma-client-js/issues/707
   */
  export interface Prisma__TeamClient<T, Null = never, ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs, GlobalOmitOptions = {}> extends Prisma.PrismaPromise<T> {
    readonly [Symbol.toStringTag]: "PrismaPromise"
    members<T extends Team$membersArgs<ExtArgs> = {}>(args?: Subset<T, Team$membersArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$TeamMemberPayload<ExtArgs>, T, "findMany", GlobalOmitOptions> | Null>
    boardTeams<T extends Team$boardTeamsArgs<ExtArgs> = {}>(args?: Subset<T, Team$boardTeamsArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$BoardTeamPayload<ExtArgs>, T, "findMany", GlobalOmitOptions> | Null>
    playerBoards<T extends Team$playerBoardsArgs<ExtArgs> = {}>(args?: Subset<T, Team$playerBoardsArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$PlayerBoardPayload<ExtArgs>, T, "findMany", GlobalOmitOptions> | Null>
    /**
     * Attaches callbacks for the resolution and/or rejection of the Promise.
     * @param onfulfilled The callback to execute when the Promise is resolved.
     * @param onrejected The callback to execute when the Promise is rejected.
     * @returns A Promise for the completion of which ever callback is executed.
     */
    then<TResult1 = T, TResult2 = never>(onfulfilled?: ((value: T) => TResult1 | PromiseLike<TResult1>) | undefined | null, onrejected?: ((reason: any) => TResult2 | PromiseLike<TResult2>) | undefined | null): $Utils.JsPromise<TResult1 | TResult2>
    /**
     * Attaches a callback for only the rejection of the Promise.
     * @param onrejected The callback to execute when the Promise is rejected.
     * @returns A Promise for the completion of the callback.
     */
    catch<TResult = never>(onrejected?: ((reason: any) => TResult | PromiseLike<TResult>) | undefined | null): $Utils.JsPromise<T | TResult>
    /**
     * Attaches a callback that is invoked when the Promise is settled (fulfilled or rejected). The
     * resolved value cannot be modified from the callback.
     * @param onfinally The callback to execute when the Promise is settled (fulfilled or rejected).
     * @returns A Promise for the completion of the callback.
     */
    finally(onfinally?: (() => void) | undefined | null): $Utils.JsPromise<T>
  }




  /**
   * Fields of the Team model
   */
  interface TeamFieldRefs {
    readonly id: FieldRef<"Team", 'String'>
    readonly name: FieldRef<"Team", 'String'>
    readonly iconUrl: FieldRef<"Team", 'String'>
    readonly createdAt: FieldRef<"Team", 'DateTime'>
    readonly updatedAt: FieldRef<"Team", 'DateTime'>
  }
    

  // Custom InputTypes
  /**
   * Team findUnique
   */
  export type TeamFindUniqueArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Team
     */
    select?: TeamSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Team
     */
    omit?: TeamOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TeamInclude<ExtArgs> | null
    /**
     * Filter, which Team to fetch.
     */
    where: TeamWhereUniqueInput
  }

  /**
   * Team findUniqueOrThrow
   */
  export type TeamFindUniqueOrThrowArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Team
     */
    select?: TeamSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Team
     */
    omit?: TeamOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TeamInclude<ExtArgs> | null
    /**
     * Filter, which Team to fetch.
     */
    where: TeamWhereUniqueInput
  }

  /**
   * Team findFirst
   */
  export type TeamFindFirstArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Team
     */
    select?: TeamSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Team
     */
    omit?: TeamOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TeamInclude<ExtArgs> | null
    /**
     * Filter, which Team to fetch.
     */
    where?: TeamWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of Teams to fetch.
     */
    orderBy?: TeamOrderByWithRelationInput | TeamOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the position for searching for Teams.
     */
    cursor?: TeamWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` Teams from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` Teams.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/distinct Distinct Docs}
     * 
     * Filter by unique combinations of Teams.
     */
    distinct?: TeamScalarFieldEnum | TeamScalarFieldEnum[]
  }

  /**
   * Team findFirstOrThrow
   */
  export type TeamFindFirstOrThrowArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Team
     */
    select?: TeamSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Team
     */
    omit?: TeamOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TeamInclude<ExtArgs> | null
    /**
     * Filter, which Team to fetch.
     */
    where?: TeamWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of Teams to fetch.
     */
    orderBy?: TeamOrderByWithRelationInput | TeamOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the position for searching for Teams.
     */
    cursor?: TeamWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` Teams from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` Teams.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/distinct Distinct Docs}
     * 
     * Filter by unique combinations of Teams.
     */
    distinct?: TeamScalarFieldEnum | TeamScalarFieldEnum[]
  }

  /**
   * Team findMany
   */
  export type TeamFindManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Team
     */
    select?: TeamSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Team
     */
    omit?: TeamOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TeamInclude<ExtArgs> | null
    /**
     * Filter, which Teams to fetch.
     */
    where?: TeamWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of Teams to fetch.
     */
    orderBy?: TeamOrderByWithRelationInput | TeamOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the position for listing Teams.
     */
    cursor?: TeamWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` Teams from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` Teams.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/distinct Distinct Docs}
     * 
     * Filter by unique combinations of Teams.
     */
    distinct?: TeamScalarFieldEnum | TeamScalarFieldEnum[]
  }

  /**
   * Team create
   */
  export type TeamCreateArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Team
     */
    select?: TeamSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Team
     */
    omit?: TeamOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TeamInclude<ExtArgs> | null
    /**
     * The data needed to create a Team.
     */
    data: XOR<TeamCreateInput, TeamUncheckedCreateInput>
  }

  /**
   * Team createMany
   */
  export type TeamCreateManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * The data used to create many Teams.
     */
    data: TeamCreateManyInput | TeamCreateManyInput[]
    skipDuplicates?: boolean
  }

  /**
   * Team createManyAndReturn
   */
  export type TeamCreateManyAndReturnArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Team
     */
    select?: TeamSelectCreateManyAndReturn<ExtArgs> | null
    /**
     * Omit specific fields from the Team
     */
    omit?: TeamOmit<ExtArgs> | null
    /**
     * The data used to create many Teams.
     */
    data: TeamCreateManyInput | TeamCreateManyInput[]
    skipDuplicates?: boolean
  }

  /**
   * Team update
   */
  export type TeamUpdateArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Team
     */
    select?: TeamSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Team
     */
    omit?: TeamOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TeamInclude<ExtArgs> | null
    /**
     * The data needed to update a Team.
     */
    data: XOR<TeamUpdateInput, TeamUncheckedUpdateInput>
    /**
     * Choose, which Team to update.
     */
    where: TeamWhereUniqueInput
  }

  /**
   * Team updateMany
   */
  export type TeamUpdateManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * The data used to update Teams.
     */
    data: XOR<TeamUpdateManyMutationInput, TeamUncheckedUpdateManyInput>
    /**
     * Filter which Teams to update
     */
    where?: TeamWhereInput
    /**
     * Limit how many Teams to update.
     */
    limit?: number
  }

  /**
   * Team updateManyAndReturn
   */
  export type TeamUpdateManyAndReturnArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Team
     */
    select?: TeamSelectUpdateManyAndReturn<ExtArgs> | null
    /**
     * Omit specific fields from the Team
     */
    omit?: TeamOmit<ExtArgs> | null
    /**
     * The data used to update Teams.
     */
    data: XOR<TeamUpdateManyMutationInput, TeamUncheckedUpdateManyInput>
    /**
     * Filter which Teams to update
     */
    where?: TeamWhereInput
    /**
     * Limit how many Teams to update.
     */
    limit?: number
  }

  /**
   * Team upsert
   */
  export type TeamUpsertArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Team
     */
    select?: TeamSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Team
     */
    omit?: TeamOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TeamInclude<ExtArgs> | null
    /**
     * The filter to search for the Team to update in case it exists.
     */
    where: TeamWhereUniqueInput
    /**
     * In case the Team found by the `where` argument doesn't exist, create a new Team with this data.
     */
    create: XOR<TeamCreateInput, TeamUncheckedCreateInput>
    /**
     * In case the Team was found with the provided `where` argument, update it with this data.
     */
    update: XOR<TeamUpdateInput, TeamUncheckedUpdateInput>
  }

  /**
   * Team delete
   */
  export type TeamDeleteArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Team
     */
    select?: TeamSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Team
     */
    omit?: TeamOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TeamInclude<ExtArgs> | null
    /**
     * Filter which Team to delete.
     */
    where: TeamWhereUniqueInput
  }

  /**
   * Team deleteMany
   */
  export type TeamDeleteManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Filter which Teams to delete
     */
    where?: TeamWhereInput
    /**
     * Limit how many Teams to delete.
     */
    limit?: number
  }

  /**
   * Team.members
   */
  export type Team$membersArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the TeamMember
     */
    select?: TeamMemberSelect<ExtArgs> | null
    /**
     * Omit specific fields from the TeamMember
     */
    omit?: TeamMemberOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TeamMemberInclude<ExtArgs> | null
    where?: TeamMemberWhereInput
    orderBy?: TeamMemberOrderByWithRelationInput | TeamMemberOrderByWithRelationInput[]
    cursor?: TeamMemberWhereUniqueInput
    take?: number
    skip?: number
    distinct?: TeamMemberScalarFieldEnum | TeamMemberScalarFieldEnum[]
  }

  /**
   * Team.boardTeams
   */
  export type Team$boardTeamsArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the BoardTeam
     */
    select?: BoardTeamSelect<ExtArgs> | null
    /**
     * Omit specific fields from the BoardTeam
     */
    omit?: BoardTeamOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: BoardTeamInclude<ExtArgs> | null
    where?: BoardTeamWhereInput
    orderBy?: BoardTeamOrderByWithRelationInput | BoardTeamOrderByWithRelationInput[]
    cursor?: BoardTeamWhereUniqueInput
    take?: number
    skip?: number
    distinct?: BoardTeamScalarFieldEnum | BoardTeamScalarFieldEnum[]
  }

  /**
   * Team.playerBoards
   */
  export type Team$playerBoardsArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the PlayerBoard
     */
    select?: PlayerBoardSelect<ExtArgs> | null
    /**
     * Omit specific fields from the PlayerBoard
     */
    omit?: PlayerBoardOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: PlayerBoardInclude<ExtArgs> | null
    where?: PlayerBoardWhereInput
    orderBy?: PlayerBoardOrderByWithRelationInput | PlayerBoardOrderByWithRelationInput[]
    cursor?: PlayerBoardWhereUniqueInput
    take?: number
    skip?: number
    distinct?: PlayerBoardScalarFieldEnum | PlayerBoardScalarFieldEnum[]
  }

  /**
   * Team without action
   */
  export type TeamDefaultArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the Team
     */
    select?: TeamSelect<ExtArgs> | null
    /**
     * Omit specific fields from the Team
     */
    omit?: TeamOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TeamInclude<ExtArgs> | null
  }


  /**
   * Model TeamMember
   */

  export type AggregateTeamMember = {
    _count: TeamMemberCountAggregateOutputType | null
    _min: TeamMemberMinAggregateOutputType | null
    _max: TeamMemberMaxAggregateOutputType | null
  }

  export type TeamMemberMinAggregateOutputType = {
    id: string | null
    teamId: string | null
    userId: string | null
    createdAt: Date | null
  }

  export type TeamMemberMaxAggregateOutputType = {
    id: string | null
    teamId: string | null
    userId: string | null
    createdAt: Date | null
  }

  export type TeamMemberCountAggregateOutputType = {
    id: number
    teamId: number
    userId: number
    createdAt: number
    _all: number
  }


  export type TeamMemberMinAggregateInputType = {
    id?: true
    teamId?: true
    userId?: true
    createdAt?: true
  }

  export type TeamMemberMaxAggregateInputType = {
    id?: true
    teamId?: true
    userId?: true
    createdAt?: true
  }

  export type TeamMemberCountAggregateInputType = {
    id?: true
    teamId?: true
    userId?: true
    createdAt?: true
    _all?: true
  }

  export type TeamMemberAggregateArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Filter which TeamMember to aggregate.
     */
    where?: TeamMemberWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of TeamMembers to fetch.
     */
    orderBy?: TeamMemberOrderByWithRelationInput | TeamMemberOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the start position
     */
    cursor?: TeamMemberWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` TeamMembers from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` TeamMembers.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Count returned TeamMembers
    **/
    _count?: true | TeamMemberCountAggregateInputType
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Select which fields to find the minimum value
    **/
    _min?: TeamMemberMinAggregateInputType
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/aggregations Aggregation Docs}
     * 
     * Select which fields to find the maximum value
    **/
    _max?: TeamMemberMaxAggregateInputType
  }

  export type GetTeamMemberAggregateType<T extends TeamMemberAggregateArgs> = {
        [P in keyof T & keyof AggregateTeamMember]: P extends '_count' | 'count'
      ? T[P] extends true
        ? number
        : GetScalarType<T[P], AggregateTeamMember[P]>
      : GetScalarType<T[P], AggregateTeamMember[P]>
  }




  export type TeamMemberGroupByArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    where?: TeamMemberWhereInput
    orderBy?: TeamMemberOrderByWithAggregationInput | TeamMemberOrderByWithAggregationInput[]
    by: TeamMemberScalarFieldEnum[] | TeamMemberScalarFieldEnum
    having?: TeamMemberScalarWhereWithAggregatesInput
    take?: number
    skip?: number
    _count?: TeamMemberCountAggregateInputType | true
    _min?: TeamMemberMinAggregateInputType
    _max?: TeamMemberMaxAggregateInputType
  }

  export type TeamMemberGroupByOutputType = {
    id: string
    teamId: string
    userId: string
    createdAt: Date
    _count: TeamMemberCountAggregateOutputType | null
    _min: TeamMemberMinAggregateOutputType | null
    _max: TeamMemberMaxAggregateOutputType | null
  }

  type GetTeamMemberGroupByPayload<T extends TeamMemberGroupByArgs> = Prisma.PrismaPromise<
    Array<
      PickEnumerable<TeamMemberGroupByOutputType, T['by']> &
        {
          [P in ((keyof T) & (keyof TeamMemberGroupByOutputType))]: P extends '_count'
            ? T[P] extends boolean
              ? number
              : GetScalarType<T[P], TeamMemberGroupByOutputType[P]>
            : GetScalarType<T[P], TeamMemberGroupByOutputType[P]>
        }
      >
    >


  export type TeamMemberSelect<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetSelect<{
    id?: boolean
    teamId?: boolean
    userId?: boolean
    createdAt?: boolean
    team?: boolean | TeamDefaultArgs<ExtArgs>
    user?: boolean | UserDefaultArgs<ExtArgs>
  }, ExtArgs["result"]["teamMember"]>

  export type TeamMemberSelectCreateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetSelect<{
    id?: boolean
    teamId?: boolean
    userId?: boolean
    createdAt?: boolean
    team?: boolean | TeamDefaultArgs<ExtArgs>
    user?: boolean | UserDefaultArgs<ExtArgs>
  }, ExtArgs["result"]["teamMember"]>

  export type TeamMemberSelectUpdateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetSelect<{
    id?: boolean
    teamId?: boolean
    userId?: boolean
    createdAt?: boolean
    team?: boolean | TeamDefaultArgs<ExtArgs>
    user?: boolean | UserDefaultArgs<ExtArgs>
  }, ExtArgs["result"]["teamMember"]>

  export type TeamMemberSelectScalar = {
    id?: boolean
    teamId?: boolean
    userId?: boolean
    createdAt?: boolean
  }

  export type TeamMemberOmit<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = $Extensions.GetOmit<"id" | "teamId" | "userId" | "createdAt", ExtArgs["result"]["teamMember"]>
  export type TeamMemberInclude<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    team?: boolean | TeamDefaultArgs<ExtArgs>
    user?: boolean | UserDefaultArgs<ExtArgs>
  }
  export type TeamMemberIncludeCreateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    team?: boolean | TeamDefaultArgs<ExtArgs>
    user?: boolean | UserDefaultArgs<ExtArgs>
  }
  export type TeamMemberIncludeUpdateManyAndReturn<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    team?: boolean | TeamDefaultArgs<ExtArgs>
    user?: boolean | UserDefaultArgs<ExtArgs>
  }

  export type $TeamMemberPayload<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    name: "TeamMember"
    objects: {
      team: Prisma.$TeamPayload<ExtArgs>
      user: Prisma.$UserPayload<ExtArgs>
    }
    scalars: $Extensions.GetPayloadResult<{
      id: string
      teamId: string
      userId: string
      createdAt: Date
    }, ExtArgs["result"]["teamMember"]>
    composites: {}
  }

  type TeamMemberGetPayload<S extends boolean | null | undefined | TeamMemberDefaultArgs> = $Result.GetResult<Prisma.$TeamMemberPayload, S>

  type TeamMemberCountArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> =
    Omit<TeamMemberFindManyArgs, 'select' | 'include' | 'distinct' | 'omit'> & {
      select?: TeamMemberCountAggregateInputType | true
    }

  export interface TeamMemberDelegate<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs, GlobalOmitOptions = {}> {
    [K: symbol]: { types: Prisma.TypeMap<ExtArgs>['model']['TeamMember'], meta: { name: 'TeamMember' } }
    /**
     * Find zero or one TeamMember that matches the filter.
     * @param {TeamMemberFindUniqueArgs} args - Arguments to find a TeamMember
     * @example
     * // Get one TeamMember
     * const teamMember = await prisma.teamMember.findUnique({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findUnique<T extends TeamMemberFindUniqueArgs>(args: SelectSubset<T, TeamMemberFindUniqueArgs<ExtArgs>>): Prisma__TeamMemberClient<$Result.GetResult<Prisma.$TeamMemberPayload<ExtArgs>, T, "findUnique", GlobalOmitOptions> | null, null, ExtArgs, GlobalOmitOptions>

    /**
     * Find one TeamMember that matches the filter or throw an error with `error.code='P2025'`
     * if no matches were found.
     * @param {TeamMemberFindUniqueOrThrowArgs} args - Arguments to find a TeamMember
     * @example
     * // Get one TeamMember
     * const teamMember = await prisma.teamMember.findUniqueOrThrow({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findUniqueOrThrow<T extends TeamMemberFindUniqueOrThrowArgs>(args: SelectSubset<T, TeamMemberFindUniqueOrThrowArgs<ExtArgs>>): Prisma__TeamMemberClient<$Result.GetResult<Prisma.$TeamMemberPayload<ExtArgs>, T, "findUniqueOrThrow", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Find the first TeamMember that matches the filter.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {TeamMemberFindFirstArgs} args - Arguments to find a TeamMember
     * @example
     * // Get one TeamMember
     * const teamMember = await prisma.teamMember.findFirst({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findFirst<T extends TeamMemberFindFirstArgs>(args?: SelectSubset<T, TeamMemberFindFirstArgs<ExtArgs>>): Prisma__TeamMemberClient<$Result.GetResult<Prisma.$TeamMemberPayload<ExtArgs>, T, "findFirst", GlobalOmitOptions> | null, null, ExtArgs, GlobalOmitOptions>

    /**
     * Find the first TeamMember that matches the filter or
     * throw `PrismaKnownClientError` with `P2025` code if no matches were found.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {TeamMemberFindFirstOrThrowArgs} args - Arguments to find a TeamMember
     * @example
     * // Get one TeamMember
     * const teamMember = await prisma.teamMember.findFirstOrThrow({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     */
    findFirstOrThrow<T extends TeamMemberFindFirstOrThrowArgs>(args?: SelectSubset<T, TeamMemberFindFirstOrThrowArgs<ExtArgs>>): Prisma__TeamMemberClient<$Result.GetResult<Prisma.$TeamMemberPayload<ExtArgs>, T, "findFirstOrThrow", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Find zero or more TeamMembers that matches the filter.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {TeamMemberFindManyArgs} args - Arguments to filter and select certain fields only.
     * @example
     * // Get all TeamMembers
     * const teamMembers = await prisma.teamMember.findMany()
     * 
     * // Get first 10 TeamMembers
     * const teamMembers = await prisma.teamMember.findMany({ take: 10 })
     * 
     * // Only select the `id`
     * const teamMemberWithIdOnly = await prisma.teamMember.findMany({ select: { id: true } })
     * 
     */
    findMany<T extends TeamMemberFindManyArgs>(args?: SelectSubset<T, TeamMemberFindManyArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$TeamMemberPayload<ExtArgs>, T, "findMany", GlobalOmitOptions>>

    /**
     * Create a TeamMember.
     * @param {TeamMemberCreateArgs} args - Arguments to create a TeamMember.
     * @example
     * // Create one TeamMember
     * const TeamMember = await prisma.teamMember.create({
     *   data: {
     *     // ... data to create a TeamMember
     *   }
     * })
     * 
     */
    create<T extends TeamMemberCreateArgs>(args: SelectSubset<T, TeamMemberCreateArgs<ExtArgs>>): Prisma__TeamMemberClient<$Result.GetResult<Prisma.$TeamMemberPayload<ExtArgs>, T, "create", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Create many TeamMembers.
     * @param {TeamMemberCreateManyArgs} args - Arguments to create many TeamMembers.
     * @example
     * // Create many TeamMembers
     * const teamMember = await prisma.teamMember.createMany({
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     *     
     */
    createMany<T extends TeamMemberCreateManyArgs>(args?: SelectSubset<T, TeamMemberCreateManyArgs<ExtArgs>>): Prisma.PrismaPromise<BatchPayload>

    /**
     * Create many TeamMembers and returns the data saved in the database.
     * @param {TeamMemberCreateManyAndReturnArgs} args - Arguments to create many TeamMembers.
     * @example
     * // Create many TeamMembers
     * const teamMember = await prisma.teamMember.createManyAndReturn({
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * 
     * // Create many TeamMembers and only return the `id`
     * const teamMemberWithIdOnly = await prisma.teamMember.createManyAndReturn({
     *   select: { id: true },
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * 
     */
    createManyAndReturn<T extends TeamMemberCreateManyAndReturnArgs>(args?: SelectSubset<T, TeamMemberCreateManyAndReturnArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$TeamMemberPayload<ExtArgs>, T, "createManyAndReturn", GlobalOmitOptions>>

    /**
     * Delete a TeamMember.
     * @param {TeamMemberDeleteArgs} args - Arguments to delete one TeamMember.
     * @example
     * // Delete one TeamMember
     * const TeamMember = await prisma.teamMember.delete({
     *   where: {
     *     // ... filter to delete one TeamMember
     *   }
     * })
     * 
     */
    delete<T extends TeamMemberDeleteArgs>(args: SelectSubset<T, TeamMemberDeleteArgs<ExtArgs>>): Prisma__TeamMemberClient<$Result.GetResult<Prisma.$TeamMemberPayload<ExtArgs>, T, "delete", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Update one TeamMember.
     * @param {TeamMemberUpdateArgs} args - Arguments to update one TeamMember.
     * @example
     * // Update one TeamMember
     * const teamMember = await prisma.teamMember.update({
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: {
     *     // ... provide data here
     *   }
     * })
     * 
     */
    update<T extends TeamMemberUpdateArgs>(args: SelectSubset<T, TeamMemberUpdateArgs<ExtArgs>>): Prisma__TeamMemberClient<$Result.GetResult<Prisma.$TeamMemberPayload<ExtArgs>, T, "update", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>

    /**
     * Delete zero or more TeamMembers.
     * @param {TeamMemberDeleteManyArgs} args - Arguments to filter TeamMembers to delete.
     * @example
     * // Delete a few TeamMembers
     * const { count } = await prisma.teamMember.deleteMany({
     *   where: {
     *     // ... provide filter here
     *   }
     * })
     * 
     */
    deleteMany<T extends TeamMemberDeleteManyArgs>(args?: SelectSubset<T, TeamMemberDeleteManyArgs<ExtArgs>>): Prisma.PrismaPromise<BatchPayload>

    /**
     * Update zero or more TeamMembers.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {TeamMemberUpdateManyArgs} args - Arguments to update one or more rows.
     * @example
     * // Update many TeamMembers
     * const teamMember = await prisma.teamMember.updateMany({
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: {
     *     // ... provide data here
     *   }
     * })
     * 
     */
    updateMany<T extends TeamMemberUpdateManyArgs>(args: SelectSubset<T, TeamMemberUpdateManyArgs<ExtArgs>>): Prisma.PrismaPromise<BatchPayload>

    /**
     * Update zero or more TeamMembers and returns the data updated in the database.
     * @param {TeamMemberUpdateManyAndReturnArgs} args - Arguments to update many TeamMembers.
     * @example
     * // Update many TeamMembers
     * const teamMember = await prisma.teamMember.updateManyAndReturn({
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * 
     * // Update zero or more TeamMembers and only return the `id`
     * const teamMemberWithIdOnly = await prisma.teamMember.updateManyAndReturn({
     *   select: { id: true },
     *   where: {
     *     // ... provide filter here
     *   },
     *   data: [
     *     // ... provide data here
     *   ]
     * })
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * 
     */
    updateManyAndReturn<T extends TeamMemberUpdateManyAndReturnArgs>(args: SelectSubset<T, TeamMemberUpdateManyAndReturnArgs<ExtArgs>>): Prisma.PrismaPromise<$Result.GetResult<Prisma.$TeamMemberPayload<ExtArgs>, T, "updateManyAndReturn", GlobalOmitOptions>>

    /**
     * Create or update one TeamMember.
     * @param {TeamMemberUpsertArgs} args - Arguments to update or create a TeamMember.
     * @example
     * // Update or create a TeamMember
     * const teamMember = await prisma.teamMember.upsert({
     *   create: {
     *     // ... data to create a TeamMember
     *   },
     *   update: {
     *     // ... in case it already exists, update
     *   },
     *   where: {
     *     // ... the filter for the TeamMember we want to update
     *   }
     * })
     */
    upsert<T extends TeamMemberUpsertArgs>(args: SelectSubset<T, TeamMemberUpsertArgs<ExtArgs>>): Prisma__TeamMemberClient<$Result.GetResult<Prisma.$TeamMemberPayload<ExtArgs>, T, "upsert", GlobalOmitOptions>, never, ExtArgs, GlobalOmitOptions>


    /**
     * Count the number of TeamMembers.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {TeamMemberCountArgs} args - Arguments to filter TeamMembers to count.
     * @example
     * // Count the number of TeamMembers
     * const count = await prisma.teamMember.count({
     *   where: {
     *     // ... the filter for the TeamMembers we want to count
     *   }
     * })
    **/
    count<T extends TeamMemberCountArgs>(
      args?: Subset<T, TeamMemberCountArgs>,
    ): Prisma.PrismaPromise<
      T extends $Utils.Record<'select', any>
        ? T['select'] extends true
          ? number
          : GetScalarType<T['select'], TeamMemberCountAggregateOutputType>
        : number
    >

    /**
     * Allows you to perform aggregations operations on a TeamMember.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {TeamMemberAggregateArgs} args - Select which aggregations you would like to apply and on what fields.
     * @example
     * // Ordered by age ascending
     * // Where email contains prisma.io
     * // Limited to the 10 users
     * const aggregations = await prisma.user.aggregate({
     *   _avg: {
     *     age: true,
     *   },
     *   where: {
     *     email: {
     *       contains: "prisma.io",
     *     },
     *   },
     *   orderBy: {
     *     age: "asc",
     *   },
     *   take: 10,
     * })
    **/
    aggregate<T extends TeamMemberAggregateArgs>(args: Subset<T, TeamMemberAggregateArgs>): Prisma.PrismaPromise<GetTeamMemberAggregateType<T>>

    /**
     * Group by TeamMember.
     * Note, that providing `undefined` is treated as the value not being there.
     * Read more here: https://pris.ly/d/null-undefined
     * @param {TeamMemberGroupByArgs} args - Group by arguments.
     * @example
     * // Group by city, order by createdAt, get count
     * const result = await prisma.user.groupBy({
     *   by: ['city', 'createdAt'],
     *   orderBy: {
     *     createdAt: true
     *   },
     *   _count: {
     *     _all: true
     *   },
     * })
     * 
    **/
    groupBy<
      T extends TeamMemberGroupByArgs,
      HasSelectOrTake extends Or<
        Extends<'skip', Keys<T>>,
        Extends<'take', Keys<T>>
      >,
      OrderByArg extends True extends HasSelectOrTake
        ? { orderBy: TeamMemberGroupByArgs['orderBy'] }
        : { orderBy?: TeamMemberGroupByArgs['orderBy'] },
      OrderFields extends ExcludeUnderscoreKeys<Keys<MaybeTupleToUnion<T['orderBy']>>>,
      ByFields extends MaybeTupleToUnion<T['by']>,
      ByValid extends Has<ByFields, OrderFields>,
      HavingFields extends GetHavingFields<T['having']>,
      HavingValid extends Has<ByFields, HavingFields>,
      ByEmpty extends T['by'] extends never[] ? True : False,
      InputErrors extends ByEmpty extends True
      ? `Error: "by" must not be empty.`
      : HavingValid extends False
      ? {
          [P in HavingFields]: P extends ByFields
            ? never
            : P extends string
            ? `Error: Field "${P}" used in "having" needs to be provided in "by".`
            : [
                Error,
                'Field ',
                P,
                ` in "having" needs to be provided in "by"`,
              ]
        }[HavingFields]
      : 'take' extends Keys<T>
      ? 'orderBy' extends Keys<T>
        ? ByValid extends True
          ? {}
          : {
              [P in OrderFields]: P extends ByFields
                ? never
                : `Error: Field "${P}" in "orderBy" needs to be provided in "by"`
            }[OrderFields]
        : 'Error: If you provide "take", you also need to provide "orderBy"'
      : 'skip' extends Keys<T>
      ? 'orderBy' extends Keys<T>
        ? ByValid extends True
          ? {}
          : {
              [P in OrderFields]: P extends ByFields
                ? never
                : `Error: Field "${P}" in "orderBy" needs to be provided in "by"`
            }[OrderFields]
        : 'Error: If you provide "skip", you also need to provide "orderBy"'
      : ByValid extends True
      ? {}
      : {
          [P in OrderFields]: P extends ByFields
            ? never
            : `Error: Field "${P}" in "orderBy" needs to be provided in "by"`
        }[OrderFields]
    >(args: SubsetIntersection<T, TeamMemberGroupByArgs, OrderByArg> & InputErrors): {} extends InputErrors ? GetTeamMemberGroupByPayload<T> : Prisma.PrismaPromise<InputErrors>
  /**
   * Fields of the TeamMember model
   */
  readonly fields: TeamMemberFieldRefs;
  }

  /**
   * The delegate class that acts as a "Promise-like" for TeamMember.
   * Why is this prefixed with `Prisma__`?
   * Because we want to prevent naming conflicts as mentioned in
   * https://github.com/prisma/prisma-client-js/issues/707
   */
  export interface Prisma__TeamMemberClient<T, Null = never, ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs, GlobalOmitOptions = {}> extends Prisma.PrismaPromise<T> {
    readonly [Symbol.toStringTag]: "PrismaPromise"
    team<T extends TeamDefaultArgs<ExtArgs> = {}>(args?: Subset<T, TeamDefaultArgs<ExtArgs>>): Prisma__TeamClient<$Result.GetResult<Prisma.$TeamPayload<ExtArgs>, T, "findUniqueOrThrow", GlobalOmitOptions> | Null, Null, ExtArgs, GlobalOmitOptions>
    user<T extends UserDefaultArgs<ExtArgs> = {}>(args?: Subset<T, UserDefaultArgs<ExtArgs>>): Prisma__UserClient<$Result.GetResult<Prisma.$UserPayload<ExtArgs>, T, "findUniqueOrThrow", GlobalOmitOptions> | Null, Null, ExtArgs, GlobalOmitOptions>
    /**
     * Attaches callbacks for the resolution and/or rejection of the Promise.
     * @param onfulfilled The callback to execute when the Promise is resolved.
     * @param onrejected The callback to execute when the Promise is rejected.
     * @returns A Promise for the completion of which ever callback is executed.
     */
    then<TResult1 = T, TResult2 = never>(onfulfilled?: ((value: T) => TResult1 | PromiseLike<TResult1>) | undefined | null, onrejected?: ((reason: any) => TResult2 | PromiseLike<TResult2>) | undefined | null): $Utils.JsPromise<TResult1 | TResult2>
    /**
     * Attaches a callback for only the rejection of the Promise.
     * @param onrejected The callback to execute when the Promise is rejected.
     * @returns A Promise for the completion of the callback.
     */
    catch<TResult = never>(onrejected?: ((reason: any) => TResult | PromiseLike<TResult>) | undefined | null): $Utils.JsPromise<T | TResult>
    /**
     * Attaches a callback that is invoked when the Promise is settled (fulfilled or rejected). The
     * resolved value cannot be modified from the callback.
     * @param onfinally The callback to execute when the Promise is settled (fulfilled or rejected).
     * @returns A Promise for the completion of the callback.
     */
    finally(onfinally?: (() => void) | undefined | null): $Utils.JsPromise<T>
  }




  /**
   * Fields of the TeamMember model
   */
  interface TeamMemberFieldRefs {
    readonly id: FieldRef<"TeamMember", 'String'>
    readonly teamId: FieldRef<"TeamMember", 'String'>
    readonly userId: FieldRef<"TeamMember", 'String'>
    readonly createdAt: FieldRef<"TeamMember", 'DateTime'>
  }
    

  // Custom InputTypes
  /**
   * TeamMember findUnique
   */
  export type TeamMemberFindUniqueArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the TeamMember
     */
    select?: TeamMemberSelect<ExtArgs> | null
    /**
     * Omit specific fields from the TeamMember
     */
    omit?: TeamMemberOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TeamMemberInclude<ExtArgs> | null
    /**
     * Filter, which TeamMember to fetch.
     */
    where: TeamMemberWhereUniqueInput
  }

  /**
   * TeamMember findUniqueOrThrow
   */
  export type TeamMemberFindUniqueOrThrowArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the TeamMember
     */
    select?: TeamMemberSelect<ExtArgs> | null
    /**
     * Omit specific fields from the TeamMember
     */
    omit?: TeamMemberOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TeamMemberInclude<ExtArgs> | null
    /**
     * Filter, which TeamMember to fetch.
     */
    where: TeamMemberWhereUniqueInput
  }

  /**
   * TeamMember findFirst
   */
  export type TeamMemberFindFirstArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the TeamMember
     */
    select?: TeamMemberSelect<ExtArgs> | null
    /**
     * Omit specific fields from the TeamMember
     */
    omit?: TeamMemberOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TeamMemberInclude<ExtArgs> | null
    /**
     * Filter, which TeamMember to fetch.
     */
    where?: TeamMemberWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of TeamMembers to fetch.
     */
    orderBy?: TeamMemberOrderByWithRelationInput | TeamMemberOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the position for searching for TeamMembers.
     */
    cursor?: TeamMemberWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` TeamMembers from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` TeamMembers.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/distinct Distinct Docs}
     * 
     * Filter by unique combinations of TeamMembers.
     */
    distinct?: TeamMemberScalarFieldEnum | TeamMemberScalarFieldEnum[]
  }

  /**
   * TeamMember findFirstOrThrow
   */
  export type TeamMemberFindFirstOrThrowArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the TeamMember
     */
    select?: TeamMemberSelect<ExtArgs> | null
    /**
     * Omit specific fields from the TeamMember
     */
    omit?: TeamMemberOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TeamMemberInclude<ExtArgs> | null
    /**
     * Filter, which TeamMember to fetch.
     */
    where?: TeamMemberWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of TeamMembers to fetch.
     */
    orderBy?: TeamMemberOrderByWithRelationInput | TeamMemberOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the position for searching for TeamMembers.
     */
    cursor?: TeamMemberWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` TeamMembers from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` TeamMembers.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/distinct Distinct Docs}
     * 
     * Filter by unique combinations of TeamMembers.
     */
    distinct?: TeamMemberScalarFieldEnum | TeamMemberScalarFieldEnum[]
  }

  /**
   * TeamMember findMany
   */
  export type TeamMemberFindManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the TeamMember
     */
    select?: TeamMemberSelect<ExtArgs> | null
    /**
     * Omit specific fields from the TeamMember
     */
    omit?: TeamMemberOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TeamMemberInclude<ExtArgs> | null
    /**
     * Filter, which TeamMembers to fetch.
     */
    where?: TeamMemberWhereInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/sorting Sorting Docs}
     * 
     * Determine the order of TeamMembers to fetch.
     */
    orderBy?: TeamMemberOrderByWithRelationInput | TeamMemberOrderByWithRelationInput[]
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination#cursor-based-pagination Cursor Docs}
     * 
     * Sets the position for listing TeamMembers.
     */
    cursor?: TeamMemberWhereUniqueInput
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Take `±n` TeamMembers from the position of the cursor.
     */
    take?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/pagination Pagination Docs}
     * 
     * Skip the first `n` TeamMembers.
     */
    skip?: number
    /**
     * {@link https://www.prisma.io/docs/concepts/components/prisma-client/distinct Distinct Docs}
     * 
     * Filter by unique combinations of TeamMembers.
     */
    distinct?: TeamMemberScalarFieldEnum | TeamMemberScalarFieldEnum[]
  }

  /**
   * TeamMember create
   */
  export type TeamMemberCreateArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the TeamMember
     */
    select?: TeamMemberSelect<ExtArgs> | null
    /**
     * Omit specific fields from the TeamMember
     */
    omit?: TeamMemberOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TeamMemberInclude<ExtArgs> | null
    /**
     * The data needed to create a TeamMember.
     */
    data: XOR<TeamMemberCreateInput, TeamMemberUncheckedCreateInput>
  }

  /**
   * TeamMember createMany
   */
  export type TeamMemberCreateManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * The data used to create many TeamMembers.
     */
    data: TeamMemberCreateManyInput | TeamMemberCreateManyInput[]
    skipDuplicates?: boolean
  }

  /**
   * TeamMember createManyAndReturn
   */
  export type TeamMemberCreateManyAndReturnArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the TeamMember
     */
    select?: TeamMemberSelectCreateManyAndReturn<ExtArgs> | null
    /**
     * Omit specific fields from the TeamMember
     */
    omit?: TeamMemberOmit<ExtArgs> | null
    /**
     * The data used to create many TeamMembers.
     */
    data: TeamMemberCreateManyInput | TeamMemberCreateManyInput[]
    skipDuplicates?: boolean
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TeamMemberIncludeCreateManyAndReturn<ExtArgs> | null
  }

  /**
   * TeamMember update
   */
  export type TeamMemberUpdateArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the TeamMember
     */
    select?: TeamMemberSelect<ExtArgs> | null
    /**
     * Omit specific fields from the TeamMember
     */
    omit?: TeamMemberOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TeamMemberInclude<ExtArgs> | null
    /**
     * The data needed to update a TeamMember.
     */
    data: XOR<TeamMemberUpdateInput, TeamMemberUncheckedUpdateInput>
    /**
     * Choose, which TeamMember to update.
     */
    where: TeamMemberWhereUniqueInput
  }

  /**
   * TeamMember updateMany
   */
  export type TeamMemberUpdateManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * The data used to update TeamMembers.
     */
    data: XOR<TeamMemberUpdateManyMutationInput, TeamMemberUncheckedUpdateManyInput>
    /**
     * Filter which TeamMembers to update
     */
    where?: TeamMemberWhereInput
    /**
     * Limit how many TeamMembers to update.
     */
    limit?: number
  }

  /**
   * TeamMember updateManyAndReturn
   */
  export type TeamMemberUpdateManyAndReturnArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the TeamMember
     */
    select?: TeamMemberSelectUpdateManyAndReturn<ExtArgs> | null
    /**
     * Omit specific fields from the TeamMember
     */
    omit?: TeamMemberOmit<ExtArgs> | null
    /**
     * The data used to update TeamMembers.
     */
    data: XOR<TeamMemberUpdateManyMutationInput, TeamMemberUncheckedUpdateManyInput>
    /**
     * Filter which TeamMembers to update
     */
    where?: TeamMemberWhereInput
    /**
     * Limit how many TeamMembers to update.
     */
    limit?: number
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TeamMemberIncludeUpdateManyAndReturn<ExtArgs> | null
  }

  /**
   * TeamMember upsert
   */
  export type TeamMemberUpsertArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the TeamMember
     */
    select?: TeamMemberSelect<ExtArgs> | null
    /**
     * Omit specific fields from the TeamMember
     */
    omit?: TeamMemberOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TeamMemberInclude<ExtArgs> | null
    /**
     * The filter to search for the TeamMember to update in case it exists.
     */
    where: TeamMemberWhereUniqueInput
    /**
     * In case the TeamMember found by the `where` argument doesn't exist, create a new TeamMember with this data.
     */
    create: XOR<TeamMemberCreateInput, TeamMemberUncheckedCreateInput>
    /**
     * In case the TeamMember was found with the provided `where` argument, update it with this data.
     */
    update: XOR<TeamMemberUpdateInput, TeamMemberUncheckedUpdateInput>
  }

  /**
   * TeamMember delete
   */
  export type TeamMemberDeleteArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the TeamMember
     */
    select?: TeamMemberSelect<ExtArgs> | null
    /**
     * Omit specific fields from the TeamMember
     */
    omit?: TeamMemberOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TeamMemberInclude<ExtArgs> | null
    /**
     * Filter which TeamMember to delete.
     */
    where: TeamMemberWhereUniqueInput
  }

  /**
   * TeamMember deleteMany
   */
  export type TeamMemberDeleteManyArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Filter which TeamMembers to delete
     */
    where?: TeamMemberWhereInput
    /**
     * Limit how many TeamMembers to delete.
     */
    limit?: number
  }

  /**
   * TeamMember without action
   */
  export type TeamMemberDefaultArgs<ExtArgs extends $Extensions.InternalArgs = $Extensions.DefaultArgs> = {
    /**
     * Select specific fields to fetch from the TeamMember
     */
    select?: TeamMemberSelect<ExtArgs> | null
    /**
     * Omit specific fields from the TeamMember
     */
    omit?: TeamMemberOmit<ExtArgs> | null
    /**
     * Choose, which related nodes to fetch as well
     */
    include?: TeamMemberInclude<ExtArgs> | null
  }


  /**
   * Enums
   */

  export const TransactionIsolationLevel: {
    ReadUncommitted: 'ReadUncommitted',
    ReadCommitted: 'ReadCommitted',
    RepeatableRead: 'RepeatableRead',
    Serializable: 'Serializable'
  };

  export type TransactionIsolationLevel = (typeof TransactionIsolationLevel)[keyof typeof TransactionIsolationLevel]


  export const RoleScalarFieldEnum: {
    id: 'id',
    name: 'name',
    description: 'description',
    createdAt: 'createdAt',
    updatedAt: 'updatedAt'
  };

  export type RoleScalarFieldEnum = (typeof RoleScalarFieldEnum)[keyof typeof RoleScalarFieldEnum]


  export const UserScalarFieldEnum: {
    id: 'id',
    discordId: 'discordId',
    discordUsername: 'discordUsername',
    nickname: 'nickname',
    avatarUrl: 'avatarUrl',
    createdAt: 'createdAt',
    updatedAt: 'updatedAt'
  };

  export type UserScalarFieldEnum = (typeof UserScalarFieldEnum)[keyof typeof UserScalarFieldEnum]


  export const UserPermissionScalarFieldEnum: {
    id: 'id',
    userId: 'userId',
    permissionKey: 'permissionKey',
    createdAt: 'createdAt'
  };

  export type UserPermissionScalarFieldEnum = (typeof UserPermissionScalarFieldEnum)[keyof typeof UserPermissionScalarFieldEnum]


  export const UserRoleScalarFieldEnum: {
    id: 'id',
    userId: 'userId',
    roleId: 'roleId',
    createdAt: 'createdAt'
  };

  export type UserRoleScalarFieldEnum = (typeof UserRoleScalarFieldEnum)[keyof typeof UserRoleScalarFieldEnum]


  export const TaskScalarFieldEnum: {
    id: 'id',
    title: 'title',
    iconUrl: 'iconUrl',
    description: 'description',
    createdAt: 'createdAt',
    updatedAt: 'updatedAt'
  };

  export type TaskScalarFieldEnum = (typeof TaskScalarFieldEnum)[keyof typeof TaskScalarFieldEnum]


  export const BoardScalarFieldEnum: {
    id: 'id',
    title: 'title',
    description: 'description',
    startDate: 'startDate',
    endDate: 'endDate',
    size: 'size',
    mode: 'mode',
    diceRollLimit: 'diceRollLimit',
    createdAt: 'createdAt',
    updatedAt: 'updatedAt'
  };

  export type BoardScalarFieldEnum = (typeof BoardScalarFieldEnum)[keyof typeof BoardScalarFieldEnum]


  export const BoardTeamScalarFieldEnum: {
    id: 'id',
    boardId: 'boardId',
    teamId: 'teamId',
    createdAt: 'createdAt'
  };

  export type BoardTeamScalarFieldEnum = (typeof BoardTeamScalarFieldEnum)[keyof typeof BoardTeamScalarFieldEnum]


  export const BoardAuthorScalarFieldEnum: {
    id: 'id',
    boardId: 'boardId',
    userId: 'userId',
    isOwner: 'isOwner',
    createdAt: 'createdAt'
  };

  export type BoardAuthorScalarFieldEnum = (typeof BoardAuthorScalarFieldEnum)[keyof typeof BoardAuthorScalarFieldEnum]


  export const TileScalarFieldEnum: {
    id: 'id',
    boardId: 'boardId',
    position: 'position',
    taskId: 'taskId',
    titleOverride: 'titleOverride',
    type: 'type',
    targetPosition: 'targetPosition',
    createdAt: 'createdAt',
    updatedAt: 'updatedAt'
  };

  export type TileScalarFieldEnum = (typeof TileScalarFieldEnum)[keyof typeof TileScalarFieldEnum]


  export const PlayerBoardScalarFieldEnum: {
    id: 'id',
    userId: 'userId',
    boardId: 'boardId',
    teamId: 'teamId',
    currentPosition: 'currentPosition',
    diceRollsToday: 'diceRollsToday',
    lastRollDate: 'lastRollDate',
    createdAt: 'createdAt',
    updatedAt: 'updatedAt'
  };

  export type PlayerBoardScalarFieldEnum = (typeof PlayerBoardScalarFieldEnum)[keyof typeof PlayerBoardScalarFieldEnum]


  export const CompletedTileScalarFieldEnum: {
    id: 'id',
    playerBoardId: 'playerBoardId',
    tileId: 'tileId',
    completedAt: 'completedAt',
    completedVia: 'completedVia'
  };

  export type CompletedTileScalarFieldEnum = (typeof CompletedTileScalarFieldEnum)[keyof typeof CompletedTileScalarFieldEnum]


  export const TeamScalarFieldEnum: {
    id: 'id',
    name: 'name',
    iconUrl: 'iconUrl',
    createdAt: 'createdAt',
    updatedAt: 'updatedAt'
  };

  export type TeamScalarFieldEnum = (typeof TeamScalarFieldEnum)[keyof typeof TeamScalarFieldEnum]


  export const TeamMemberScalarFieldEnum: {
    id: 'id',
    teamId: 'teamId',
    userId: 'userId',
    createdAt: 'createdAt'
  };

  export type TeamMemberScalarFieldEnum = (typeof TeamMemberScalarFieldEnum)[keyof typeof TeamMemberScalarFieldEnum]


  export const SortOrder: {
    asc: 'asc',
    desc: 'desc'
  };

  export type SortOrder = (typeof SortOrder)[keyof typeof SortOrder]


  export const QueryMode: {
    default: 'default',
    insensitive: 'insensitive'
  };

  export type QueryMode = (typeof QueryMode)[keyof typeof QueryMode]


  export const NullsOrder: {
    first: 'first',
    last: 'last'
  };

  export type NullsOrder = (typeof NullsOrder)[keyof typeof NullsOrder]


  /**
   * Field references
   */


  /**
   * Reference to a field of type 'String'
   */
  export type StringFieldRefInput<$PrismaModel> = FieldRefInputType<$PrismaModel, 'String'>
    


  /**
   * Reference to a field of type 'String[]'
   */
  export type ListStringFieldRefInput<$PrismaModel> = FieldRefInputType<$PrismaModel, 'String[]'>
    


  /**
   * Reference to a field of type 'DateTime'
   */
  export type DateTimeFieldRefInput<$PrismaModel> = FieldRefInputType<$PrismaModel, 'DateTime'>
    


  /**
   * Reference to a field of type 'DateTime[]'
   */
  export type ListDateTimeFieldRefInput<$PrismaModel> = FieldRefInputType<$PrismaModel, 'DateTime[]'>
    


  /**
   * Reference to a field of type 'BoardSize'
   */
  export type EnumBoardSizeFieldRefInput<$PrismaModel> = FieldRefInputType<$PrismaModel, 'BoardSize'>
    


  /**
   * Reference to a field of type 'BoardSize[]'
   */
  export type ListEnumBoardSizeFieldRefInput<$PrismaModel> = FieldRefInputType<$PrismaModel, 'BoardSize[]'>
    


  /**
   * Reference to a field of type 'BoardMode'
   */
  export type EnumBoardModeFieldRefInput<$PrismaModel> = FieldRefInputType<$PrismaModel, 'BoardMode'>
    


  /**
   * Reference to a field of type 'BoardMode[]'
   */
  export type ListEnumBoardModeFieldRefInput<$PrismaModel> = FieldRefInputType<$PrismaModel, 'BoardMode[]'>
    


  /**
   * Reference to a field of type 'Int'
   */
  export type IntFieldRefInput<$PrismaModel> = FieldRefInputType<$PrismaModel, 'Int'>
    


  /**
   * Reference to a field of type 'Int[]'
   */
  export type ListIntFieldRefInput<$PrismaModel> = FieldRefInputType<$PrismaModel, 'Int[]'>
    


  /**
   * Reference to a field of type 'Boolean'
   */
  export type BooleanFieldRefInput<$PrismaModel> = FieldRefInputType<$PrismaModel, 'Boolean'>
    


  /**
   * Reference to a field of type 'TileType'
   */
  export type EnumTileTypeFieldRefInput<$PrismaModel> = FieldRefInputType<$PrismaModel, 'TileType'>
    


  /**
   * Reference to a field of type 'TileType[]'
   */
  export type ListEnumTileTypeFieldRefInput<$PrismaModel> = FieldRefInputType<$PrismaModel, 'TileType[]'>
    


  /**
   * Reference to a field of type 'CompletionSource'
   */
  export type EnumCompletionSourceFieldRefInput<$PrismaModel> = FieldRefInputType<$PrismaModel, 'CompletionSource'>
    


  /**
   * Reference to a field of type 'CompletionSource[]'
   */
  export type ListEnumCompletionSourceFieldRefInput<$PrismaModel> = FieldRefInputType<$PrismaModel, 'CompletionSource[]'>
    


  /**
   * Reference to a field of type 'Float'
   */
  export type FloatFieldRefInput<$PrismaModel> = FieldRefInputType<$PrismaModel, 'Float'>
    


  /**
   * Reference to a field of type 'Float[]'
   */
  export type ListFloatFieldRefInput<$PrismaModel> = FieldRefInputType<$PrismaModel, 'Float[]'>
    
  /**
   * Deep Input Types
   */


  export type RoleWhereInput = {
    AND?: RoleWhereInput | RoleWhereInput[]
    OR?: RoleWhereInput[]
    NOT?: RoleWhereInput | RoleWhereInput[]
    id?: UuidFilter<"Role"> | string
    name?: StringFilter<"Role"> | string
    description?: StringNullableFilter<"Role"> | string | null
    createdAt?: DateTimeFilter<"Role"> | Date | string
    updatedAt?: DateTimeFilter<"Role"> | Date | string
    userRoles?: UserRoleListRelationFilter
  }

  export type RoleOrderByWithRelationInput = {
    id?: SortOrder
    name?: SortOrder
    description?: SortOrderInput | SortOrder
    createdAt?: SortOrder
    updatedAt?: SortOrder
    userRoles?: UserRoleOrderByRelationAggregateInput
  }

  export type RoleWhereUniqueInput = Prisma.AtLeast<{
    id?: string
    name?: string
    AND?: RoleWhereInput | RoleWhereInput[]
    OR?: RoleWhereInput[]
    NOT?: RoleWhereInput | RoleWhereInput[]
    description?: StringNullableFilter<"Role"> | string | null
    createdAt?: DateTimeFilter<"Role"> | Date | string
    updatedAt?: DateTimeFilter<"Role"> | Date | string
    userRoles?: UserRoleListRelationFilter
  }, "id" | "name">

  export type RoleOrderByWithAggregationInput = {
    id?: SortOrder
    name?: SortOrder
    description?: SortOrderInput | SortOrder
    createdAt?: SortOrder
    updatedAt?: SortOrder
    _count?: RoleCountOrderByAggregateInput
    _max?: RoleMaxOrderByAggregateInput
    _min?: RoleMinOrderByAggregateInput
  }

  export type RoleScalarWhereWithAggregatesInput = {
    AND?: RoleScalarWhereWithAggregatesInput | RoleScalarWhereWithAggregatesInput[]
    OR?: RoleScalarWhereWithAggregatesInput[]
    NOT?: RoleScalarWhereWithAggregatesInput | RoleScalarWhereWithAggregatesInput[]
    id?: UuidWithAggregatesFilter<"Role"> | string
    name?: StringWithAggregatesFilter<"Role"> | string
    description?: StringNullableWithAggregatesFilter<"Role"> | string | null
    createdAt?: DateTimeWithAggregatesFilter<"Role"> | Date | string
    updatedAt?: DateTimeWithAggregatesFilter<"Role"> | Date | string
  }

  export type UserWhereInput = {
    AND?: UserWhereInput | UserWhereInput[]
    OR?: UserWhereInput[]
    NOT?: UserWhereInput | UserWhereInput[]
    id?: UuidFilter<"User"> | string
    discordId?: StringFilter<"User"> | string
    discordUsername?: StringFilter<"User"> | string
    nickname?: StringNullableFilter<"User"> | string | null
    avatarUrl?: StringNullableFilter<"User"> | string | null
    createdAt?: DateTimeFilter<"User"> | Date | string
    updatedAt?: DateTimeFilter<"User"> | Date | string
    userRoles?: UserRoleListRelationFilter
    userPermissions?: UserPermissionListRelationFilter
    boardAuthors?: BoardAuthorListRelationFilter
    playerBoards?: PlayerBoardListRelationFilter
    teamMembers?: TeamMemberListRelationFilter
  }

  export type UserOrderByWithRelationInput = {
    id?: SortOrder
    discordId?: SortOrder
    discordUsername?: SortOrder
    nickname?: SortOrderInput | SortOrder
    avatarUrl?: SortOrderInput | SortOrder
    createdAt?: SortOrder
    updatedAt?: SortOrder
    userRoles?: UserRoleOrderByRelationAggregateInput
    userPermissions?: UserPermissionOrderByRelationAggregateInput
    boardAuthors?: BoardAuthorOrderByRelationAggregateInput
    playerBoards?: PlayerBoardOrderByRelationAggregateInput
    teamMembers?: TeamMemberOrderByRelationAggregateInput
  }

  export type UserWhereUniqueInput = Prisma.AtLeast<{
    id?: string
    discordId?: string
    AND?: UserWhereInput | UserWhereInput[]
    OR?: UserWhereInput[]
    NOT?: UserWhereInput | UserWhereInput[]
    discordUsername?: StringFilter<"User"> | string
    nickname?: StringNullableFilter<"User"> | string | null
    avatarUrl?: StringNullableFilter<"User"> | string | null
    createdAt?: DateTimeFilter<"User"> | Date | string
    updatedAt?: DateTimeFilter<"User"> | Date | string
    userRoles?: UserRoleListRelationFilter
    userPermissions?: UserPermissionListRelationFilter
    boardAuthors?: BoardAuthorListRelationFilter
    playerBoards?: PlayerBoardListRelationFilter
    teamMembers?: TeamMemberListRelationFilter
  }, "id" | "discordId">

  export type UserOrderByWithAggregationInput = {
    id?: SortOrder
    discordId?: SortOrder
    discordUsername?: SortOrder
    nickname?: SortOrderInput | SortOrder
    avatarUrl?: SortOrderInput | SortOrder
    createdAt?: SortOrder
    updatedAt?: SortOrder
    _count?: UserCountOrderByAggregateInput
    _max?: UserMaxOrderByAggregateInput
    _min?: UserMinOrderByAggregateInput
  }

  export type UserScalarWhereWithAggregatesInput = {
    AND?: UserScalarWhereWithAggregatesInput | UserScalarWhereWithAggregatesInput[]
    OR?: UserScalarWhereWithAggregatesInput[]
    NOT?: UserScalarWhereWithAggregatesInput | UserScalarWhereWithAggregatesInput[]
    id?: UuidWithAggregatesFilter<"User"> | string
    discordId?: StringWithAggregatesFilter<"User"> | string
    discordUsername?: StringWithAggregatesFilter<"User"> | string
    nickname?: StringNullableWithAggregatesFilter<"User"> | string | null
    avatarUrl?: StringNullableWithAggregatesFilter<"User"> | string | null
    createdAt?: DateTimeWithAggregatesFilter<"User"> | Date | string
    updatedAt?: DateTimeWithAggregatesFilter<"User"> | Date | string
  }

  export type UserPermissionWhereInput = {
    AND?: UserPermissionWhereInput | UserPermissionWhereInput[]
    OR?: UserPermissionWhereInput[]
    NOT?: UserPermissionWhereInput | UserPermissionWhereInput[]
    id?: UuidFilter<"UserPermission"> | string
    userId?: UuidFilter<"UserPermission"> | string
    permissionKey?: StringFilter<"UserPermission"> | string
    createdAt?: DateTimeFilter<"UserPermission"> | Date | string
    user?: XOR<UserScalarRelationFilter, UserWhereInput>
  }

  export type UserPermissionOrderByWithRelationInput = {
    id?: SortOrder
    userId?: SortOrder
    permissionKey?: SortOrder
    createdAt?: SortOrder
    user?: UserOrderByWithRelationInput
  }

  export type UserPermissionWhereUniqueInput = Prisma.AtLeast<{
    id?: string
    userId_permissionKey?: UserPermissionUserIdPermissionKeyCompoundUniqueInput
    AND?: UserPermissionWhereInput | UserPermissionWhereInput[]
    OR?: UserPermissionWhereInput[]
    NOT?: UserPermissionWhereInput | UserPermissionWhereInput[]
    userId?: UuidFilter<"UserPermission"> | string
    permissionKey?: StringFilter<"UserPermission"> | string
    createdAt?: DateTimeFilter<"UserPermission"> | Date | string
    user?: XOR<UserScalarRelationFilter, UserWhereInput>
  }, "id" | "userId_permissionKey">

  export type UserPermissionOrderByWithAggregationInput = {
    id?: SortOrder
    userId?: SortOrder
    permissionKey?: SortOrder
    createdAt?: SortOrder
    _count?: UserPermissionCountOrderByAggregateInput
    _max?: UserPermissionMaxOrderByAggregateInput
    _min?: UserPermissionMinOrderByAggregateInput
  }

  export type UserPermissionScalarWhereWithAggregatesInput = {
    AND?: UserPermissionScalarWhereWithAggregatesInput | UserPermissionScalarWhereWithAggregatesInput[]
    OR?: UserPermissionScalarWhereWithAggregatesInput[]
    NOT?: UserPermissionScalarWhereWithAggregatesInput | UserPermissionScalarWhereWithAggregatesInput[]
    id?: UuidWithAggregatesFilter<"UserPermission"> | string
    userId?: UuidWithAggregatesFilter<"UserPermission"> | string
    permissionKey?: StringWithAggregatesFilter<"UserPermission"> | string
    createdAt?: DateTimeWithAggregatesFilter<"UserPermission"> | Date | string
  }

  export type UserRoleWhereInput = {
    AND?: UserRoleWhereInput | UserRoleWhereInput[]
    OR?: UserRoleWhereInput[]
    NOT?: UserRoleWhereInput | UserRoleWhereInput[]
    id?: UuidFilter<"UserRole"> | string
    userId?: UuidFilter<"UserRole"> | string
    roleId?: UuidFilter<"UserRole"> | string
    createdAt?: DateTimeFilter<"UserRole"> | Date | string
    user?: XOR<UserScalarRelationFilter, UserWhereInput>
    role?: XOR<RoleScalarRelationFilter, RoleWhereInput>
  }

  export type UserRoleOrderByWithRelationInput = {
    id?: SortOrder
    userId?: SortOrder
    roleId?: SortOrder
    createdAt?: SortOrder
    user?: UserOrderByWithRelationInput
    role?: RoleOrderByWithRelationInput
  }

  export type UserRoleWhereUniqueInput = Prisma.AtLeast<{
    id?: string
    userId_roleId?: UserRoleUserIdRoleIdCompoundUniqueInput
    AND?: UserRoleWhereInput | UserRoleWhereInput[]
    OR?: UserRoleWhereInput[]
    NOT?: UserRoleWhereInput | UserRoleWhereInput[]
    userId?: UuidFilter<"UserRole"> | string
    roleId?: UuidFilter<"UserRole"> | string
    createdAt?: DateTimeFilter<"UserRole"> | Date | string
    user?: XOR<UserScalarRelationFilter, UserWhereInput>
    role?: XOR<RoleScalarRelationFilter, RoleWhereInput>
  }, "id" | "userId_roleId">

  export type UserRoleOrderByWithAggregationInput = {
    id?: SortOrder
    userId?: SortOrder
    roleId?: SortOrder
    createdAt?: SortOrder
    _count?: UserRoleCountOrderByAggregateInput
    _max?: UserRoleMaxOrderByAggregateInput
    _min?: UserRoleMinOrderByAggregateInput
  }

  export type UserRoleScalarWhereWithAggregatesInput = {
    AND?: UserRoleScalarWhereWithAggregatesInput | UserRoleScalarWhereWithAggregatesInput[]
    OR?: UserRoleScalarWhereWithAggregatesInput[]
    NOT?: UserRoleScalarWhereWithAggregatesInput | UserRoleScalarWhereWithAggregatesInput[]
    id?: UuidWithAggregatesFilter<"UserRole"> | string
    userId?: UuidWithAggregatesFilter<"UserRole"> | string
    roleId?: UuidWithAggregatesFilter<"UserRole"> | string
    createdAt?: DateTimeWithAggregatesFilter<"UserRole"> | Date | string
  }

  export type TaskWhereInput = {
    AND?: TaskWhereInput | TaskWhereInput[]
    OR?: TaskWhereInput[]
    NOT?: TaskWhereInput | TaskWhereInput[]
    id?: UuidFilter<"Task"> | string
    title?: StringFilter<"Task"> | string
    iconUrl?: StringNullableFilter<"Task"> | string | null
    description?: StringNullableFilter<"Task"> | string | null
    createdAt?: DateTimeFilter<"Task"> | Date | string
    updatedAt?: DateTimeFilter<"Task"> | Date | string
    tiles?: TileListRelationFilter
  }

  export type TaskOrderByWithRelationInput = {
    id?: SortOrder
    title?: SortOrder
    iconUrl?: SortOrderInput | SortOrder
    description?: SortOrderInput | SortOrder
    createdAt?: SortOrder
    updatedAt?: SortOrder
    tiles?: TileOrderByRelationAggregateInput
  }

  export type TaskWhereUniqueInput = Prisma.AtLeast<{
    id?: string
    AND?: TaskWhereInput | TaskWhereInput[]
    OR?: TaskWhereInput[]
    NOT?: TaskWhereInput | TaskWhereInput[]
    title?: StringFilter<"Task"> | string
    iconUrl?: StringNullableFilter<"Task"> | string | null
    description?: StringNullableFilter<"Task"> | string | null
    createdAt?: DateTimeFilter<"Task"> | Date | string
    updatedAt?: DateTimeFilter<"Task"> | Date | string
    tiles?: TileListRelationFilter
  }, "id">

  export type TaskOrderByWithAggregationInput = {
    id?: SortOrder
    title?: SortOrder
    iconUrl?: SortOrderInput | SortOrder
    description?: SortOrderInput | SortOrder
    createdAt?: SortOrder
    updatedAt?: SortOrder
    _count?: TaskCountOrderByAggregateInput
    _max?: TaskMaxOrderByAggregateInput
    _min?: TaskMinOrderByAggregateInput
  }

  export type TaskScalarWhereWithAggregatesInput = {
    AND?: TaskScalarWhereWithAggregatesInput | TaskScalarWhereWithAggregatesInput[]
    OR?: TaskScalarWhereWithAggregatesInput[]
    NOT?: TaskScalarWhereWithAggregatesInput | TaskScalarWhereWithAggregatesInput[]
    id?: UuidWithAggregatesFilter<"Task"> | string
    title?: StringWithAggregatesFilter<"Task"> | string
    iconUrl?: StringNullableWithAggregatesFilter<"Task"> | string | null
    description?: StringNullableWithAggregatesFilter<"Task"> | string | null
    createdAt?: DateTimeWithAggregatesFilter<"Task"> | Date | string
    updatedAt?: DateTimeWithAggregatesFilter<"Task"> | Date | string
  }

  export type BoardWhereInput = {
    AND?: BoardWhereInput | BoardWhereInput[]
    OR?: BoardWhereInput[]
    NOT?: BoardWhereInput | BoardWhereInput[]
    id?: UuidFilter<"Board"> | string
    title?: StringFilter<"Board"> | string
    description?: StringNullableFilter<"Board"> | string | null
    startDate?: DateTimeNullableFilter<"Board"> | Date | string | null
    endDate?: DateTimeNullableFilter<"Board"> | Date | string | null
    size?: EnumBoardSizeFilter<"Board"> | $Enums.BoardSize
    mode?: EnumBoardModeFilter<"Board"> | $Enums.BoardMode
    diceRollLimit?: IntNullableFilter<"Board"> | number | null
    createdAt?: DateTimeFilter<"Board"> | Date | string
    updatedAt?: DateTimeFilter<"Board"> | Date | string
    authors?: BoardAuthorListRelationFilter
    tiles?: TileListRelationFilter
    playerBoards?: PlayerBoardListRelationFilter
    boardTeams?: BoardTeamListRelationFilter
  }

  export type BoardOrderByWithRelationInput = {
    id?: SortOrder
    title?: SortOrder
    description?: SortOrderInput | SortOrder
    startDate?: SortOrderInput | SortOrder
    endDate?: SortOrderInput | SortOrder
    size?: SortOrder
    mode?: SortOrder
    diceRollLimit?: SortOrderInput | SortOrder
    createdAt?: SortOrder
    updatedAt?: SortOrder
    authors?: BoardAuthorOrderByRelationAggregateInput
    tiles?: TileOrderByRelationAggregateInput
    playerBoards?: PlayerBoardOrderByRelationAggregateInput
    boardTeams?: BoardTeamOrderByRelationAggregateInput
  }

  export type BoardWhereUniqueInput = Prisma.AtLeast<{
    id?: string
    AND?: BoardWhereInput | BoardWhereInput[]
    OR?: BoardWhereInput[]
    NOT?: BoardWhereInput | BoardWhereInput[]
    title?: StringFilter<"Board"> | string
    description?: StringNullableFilter<"Board"> | string | null
    startDate?: DateTimeNullableFilter<"Board"> | Date | string | null
    endDate?: DateTimeNullableFilter<"Board"> | Date | string | null
    size?: EnumBoardSizeFilter<"Board"> | $Enums.BoardSize
    mode?: EnumBoardModeFilter<"Board"> | $Enums.BoardMode
    diceRollLimit?: IntNullableFilter<"Board"> | number | null
    createdAt?: DateTimeFilter<"Board"> | Date | string
    updatedAt?: DateTimeFilter<"Board"> | Date | string
    authors?: BoardAuthorListRelationFilter
    tiles?: TileListRelationFilter
    playerBoards?: PlayerBoardListRelationFilter
    boardTeams?: BoardTeamListRelationFilter
  }, "id">

  export type BoardOrderByWithAggregationInput = {
    id?: SortOrder
    title?: SortOrder
    description?: SortOrderInput | SortOrder
    startDate?: SortOrderInput | SortOrder
    endDate?: SortOrderInput | SortOrder
    size?: SortOrder
    mode?: SortOrder
    diceRollLimit?: SortOrderInput | SortOrder
    createdAt?: SortOrder
    updatedAt?: SortOrder
    _count?: BoardCountOrderByAggregateInput
    _avg?: BoardAvgOrderByAggregateInput
    _max?: BoardMaxOrderByAggregateInput
    _min?: BoardMinOrderByAggregateInput
    _sum?: BoardSumOrderByAggregateInput
  }

  export type BoardScalarWhereWithAggregatesInput = {
    AND?: BoardScalarWhereWithAggregatesInput | BoardScalarWhereWithAggregatesInput[]
    OR?: BoardScalarWhereWithAggregatesInput[]
    NOT?: BoardScalarWhereWithAggregatesInput | BoardScalarWhereWithAggregatesInput[]
    id?: UuidWithAggregatesFilter<"Board"> | string
    title?: StringWithAggregatesFilter<"Board"> | string
    description?: StringNullableWithAggregatesFilter<"Board"> | string | null
    startDate?: DateTimeNullableWithAggregatesFilter<"Board"> | Date | string | null
    endDate?: DateTimeNullableWithAggregatesFilter<"Board"> | Date | string | null
    size?: EnumBoardSizeWithAggregatesFilter<"Board"> | $Enums.BoardSize
    mode?: EnumBoardModeWithAggregatesFilter<"Board"> | $Enums.BoardMode
    diceRollLimit?: IntNullableWithAggregatesFilter<"Board"> | number | null
    createdAt?: DateTimeWithAggregatesFilter<"Board"> | Date | string
    updatedAt?: DateTimeWithAggregatesFilter<"Board"> | Date | string
  }

  export type BoardTeamWhereInput = {
    AND?: BoardTeamWhereInput | BoardTeamWhereInput[]
    OR?: BoardTeamWhereInput[]
    NOT?: BoardTeamWhereInput | BoardTeamWhereInput[]
    id?: UuidFilter<"BoardTeam"> | string
    boardId?: UuidFilter<"BoardTeam"> | string
    teamId?: UuidFilter<"BoardTeam"> | string
    createdAt?: DateTimeFilter<"BoardTeam"> | Date | string
    board?: XOR<BoardScalarRelationFilter, BoardWhereInput>
    team?: XOR<TeamScalarRelationFilter, TeamWhereInput>
  }

  export type BoardTeamOrderByWithRelationInput = {
    id?: SortOrder
    boardId?: SortOrder
    teamId?: SortOrder
    createdAt?: SortOrder
    board?: BoardOrderByWithRelationInput
    team?: TeamOrderByWithRelationInput
  }

  export type BoardTeamWhereUniqueInput = Prisma.AtLeast<{
    id?: string
    boardId_teamId?: BoardTeamBoardIdTeamIdCompoundUniqueInput
    AND?: BoardTeamWhereInput | BoardTeamWhereInput[]
    OR?: BoardTeamWhereInput[]
    NOT?: BoardTeamWhereInput | BoardTeamWhereInput[]
    boardId?: UuidFilter<"BoardTeam"> | string
    teamId?: UuidFilter<"BoardTeam"> | string
    createdAt?: DateTimeFilter<"BoardTeam"> | Date | string
    board?: XOR<BoardScalarRelationFilter, BoardWhereInput>
    team?: XOR<TeamScalarRelationFilter, TeamWhereInput>
  }, "id" | "boardId_teamId">

  export type BoardTeamOrderByWithAggregationInput = {
    id?: SortOrder
    boardId?: SortOrder
    teamId?: SortOrder
    createdAt?: SortOrder
    _count?: BoardTeamCountOrderByAggregateInput
    _max?: BoardTeamMaxOrderByAggregateInput
    _min?: BoardTeamMinOrderByAggregateInput
  }

  export type BoardTeamScalarWhereWithAggregatesInput = {
    AND?: BoardTeamScalarWhereWithAggregatesInput | BoardTeamScalarWhereWithAggregatesInput[]
    OR?: BoardTeamScalarWhereWithAggregatesInput[]
    NOT?: BoardTeamScalarWhereWithAggregatesInput | BoardTeamScalarWhereWithAggregatesInput[]
    id?: UuidWithAggregatesFilter<"BoardTeam"> | string
    boardId?: UuidWithAggregatesFilter<"BoardTeam"> | string
    teamId?: UuidWithAggregatesFilter<"BoardTeam"> | string
    createdAt?: DateTimeWithAggregatesFilter<"BoardTeam"> | Date | string
  }

  export type BoardAuthorWhereInput = {
    AND?: BoardAuthorWhereInput | BoardAuthorWhereInput[]
    OR?: BoardAuthorWhereInput[]
    NOT?: BoardAuthorWhereInput | BoardAuthorWhereInput[]
    id?: UuidFilter<"BoardAuthor"> | string
    boardId?: UuidFilter<"BoardAuthor"> | string
    userId?: UuidFilter<"BoardAuthor"> | string
    isOwner?: BoolFilter<"BoardAuthor"> | boolean
    createdAt?: DateTimeFilter<"BoardAuthor"> | Date | string
    board?: XOR<BoardScalarRelationFilter, BoardWhereInput>
    user?: XOR<UserScalarRelationFilter, UserWhereInput>
  }

  export type BoardAuthorOrderByWithRelationInput = {
    id?: SortOrder
    boardId?: SortOrder
    userId?: SortOrder
    isOwner?: SortOrder
    createdAt?: SortOrder
    board?: BoardOrderByWithRelationInput
    user?: UserOrderByWithRelationInput
  }

  export type BoardAuthorWhereUniqueInput = Prisma.AtLeast<{
    id?: string
    boardId_userId?: BoardAuthorBoardIdUserIdCompoundUniqueInput
    AND?: BoardAuthorWhereInput | BoardAuthorWhereInput[]
    OR?: BoardAuthorWhereInput[]
    NOT?: BoardAuthorWhereInput | BoardAuthorWhereInput[]
    boardId?: UuidFilter<"BoardAuthor"> | string
    userId?: UuidFilter<"BoardAuthor"> | string
    isOwner?: BoolFilter<"BoardAuthor"> | boolean
    createdAt?: DateTimeFilter<"BoardAuthor"> | Date | string
    board?: XOR<BoardScalarRelationFilter, BoardWhereInput>
    user?: XOR<UserScalarRelationFilter, UserWhereInput>
  }, "id" | "boardId_userId">

  export type BoardAuthorOrderByWithAggregationInput = {
    id?: SortOrder
    boardId?: SortOrder
    userId?: SortOrder
    isOwner?: SortOrder
    createdAt?: SortOrder
    _count?: BoardAuthorCountOrderByAggregateInput
    _max?: BoardAuthorMaxOrderByAggregateInput
    _min?: BoardAuthorMinOrderByAggregateInput
  }

  export type BoardAuthorScalarWhereWithAggregatesInput = {
    AND?: BoardAuthorScalarWhereWithAggregatesInput | BoardAuthorScalarWhereWithAggregatesInput[]
    OR?: BoardAuthorScalarWhereWithAggregatesInput[]
    NOT?: BoardAuthorScalarWhereWithAggregatesInput | BoardAuthorScalarWhereWithAggregatesInput[]
    id?: UuidWithAggregatesFilter<"BoardAuthor"> | string
    boardId?: UuidWithAggregatesFilter<"BoardAuthor"> | string
    userId?: UuidWithAggregatesFilter<"BoardAuthor"> | string
    isOwner?: BoolWithAggregatesFilter<"BoardAuthor"> | boolean
    createdAt?: DateTimeWithAggregatesFilter<"BoardAuthor"> | Date | string
  }

  export type TileWhereInput = {
    AND?: TileWhereInput | TileWhereInput[]
    OR?: TileWhereInput[]
    NOT?: TileWhereInput | TileWhereInput[]
    id?: UuidFilter<"Tile"> | string
    boardId?: UuidFilter<"Tile"> | string
    position?: IntFilter<"Tile"> | number
    taskId?: UuidNullableFilter<"Tile"> | string | null
    titleOverride?: StringNullableFilter<"Tile"> | string | null
    type?: EnumTileTypeFilter<"Tile"> | $Enums.TileType
    targetPosition?: IntNullableFilter<"Tile"> | number | null
    createdAt?: DateTimeFilter<"Tile"> | Date | string
    updatedAt?: DateTimeFilter<"Tile"> | Date | string
    board?: XOR<BoardScalarRelationFilter, BoardWhereInput>
    task?: XOR<TaskNullableScalarRelationFilter, TaskWhereInput> | null
    completedTiles?: CompletedTileListRelationFilter
  }

  export type TileOrderByWithRelationInput = {
    id?: SortOrder
    boardId?: SortOrder
    position?: SortOrder
    taskId?: SortOrderInput | SortOrder
    titleOverride?: SortOrderInput | SortOrder
    type?: SortOrder
    targetPosition?: SortOrderInput | SortOrder
    createdAt?: SortOrder
    updatedAt?: SortOrder
    board?: BoardOrderByWithRelationInput
    task?: TaskOrderByWithRelationInput
    completedTiles?: CompletedTileOrderByRelationAggregateInput
  }

  export type TileWhereUniqueInput = Prisma.AtLeast<{
    id?: string
    boardId_position?: TileBoardIdPositionCompoundUniqueInput
    AND?: TileWhereInput | TileWhereInput[]
    OR?: TileWhereInput[]
    NOT?: TileWhereInput | TileWhereInput[]
    boardId?: UuidFilter<"Tile"> | string
    position?: IntFilter<"Tile"> | number
    taskId?: UuidNullableFilter<"Tile"> | string | null
    titleOverride?: StringNullableFilter<"Tile"> | string | null
    type?: EnumTileTypeFilter<"Tile"> | $Enums.TileType
    targetPosition?: IntNullableFilter<"Tile"> | number | null
    createdAt?: DateTimeFilter<"Tile"> | Date | string
    updatedAt?: DateTimeFilter<"Tile"> | Date | string
    board?: XOR<BoardScalarRelationFilter, BoardWhereInput>
    task?: XOR<TaskNullableScalarRelationFilter, TaskWhereInput> | null
    completedTiles?: CompletedTileListRelationFilter
  }, "id" | "boardId_position">

  export type TileOrderByWithAggregationInput = {
    id?: SortOrder
    boardId?: SortOrder
    position?: SortOrder
    taskId?: SortOrderInput | SortOrder
    titleOverride?: SortOrderInput | SortOrder
    type?: SortOrder
    targetPosition?: SortOrderInput | SortOrder
    createdAt?: SortOrder
    updatedAt?: SortOrder
    _count?: TileCountOrderByAggregateInput
    _avg?: TileAvgOrderByAggregateInput
    _max?: TileMaxOrderByAggregateInput
    _min?: TileMinOrderByAggregateInput
    _sum?: TileSumOrderByAggregateInput
  }

  export type TileScalarWhereWithAggregatesInput = {
    AND?: TileScalarWhereWithAggregatesInput | TileScalarWhereWithAggregatesInput[]
    OR?: TileScalarWhereWithAggregatesInput[]
    NOT?: TileScalarWhereWithAggregatesInput | TileScalarWhereWithAggregatesInput[]
    id?: UuidWithAggregatesFilter<"Tile"> | string
    boardId?: UuidWithAggregatesFilter<"Tile"> | string
    position?: IntWithAggregatesFilter<"Tile"> | number
    taskId?: UuidNullableWithAggregatesFilter<"Tile"> | string | null
    titleOverride?: StringNullableWithAggregatesFilter<"Tile"> | string | null
    type?: EnumTileTypeWithAggregatesFilter<"Tile"> | $Enums.TileType
    targetPosition?: IntNullableWithAggregatesFilter<"Tile"> | number | null
    createdAt?: DateTimeWithAggregatesFilter<"Tile"> | Date | string
    updatedAt?: DateTimeWithAggregatesFilter<"Tile"> | Date | string
  }

  export type PlayerBoardWhereInput = {
    AND?: PlayerBoardWhereInput | PlayerBoardWhereInput[]
    OR?: PlayerBoardWhereInput[]
    NOT?: PlayerBoardWhereInput | PlayerBoardWhereInput[]
    id?: UuidFilter<"PlayerBoard"> | string
    userId?: UuidFilter<"PlayerBoard"> | string
    boardId?: UuidFilter<"PlayerBoard"> | string
    teamId?: UuidNullableFilter<"PlayerBoard"> | string | null
    currentPosition?: IntFilter<"PlayerBoard"> | number
    diceRollsToday?: IntFilter<"PlayerBoard"> | number
    lastRollDate?: DateTimeNullableFilter<"PlayerBoard"> | Date | string | null
    createdAt?: DateTimeFilter<"PlayerBoard"> | Date | string
    updatedAt?: DateTimeFilter<"PlayerBoard"> | Date | string
    user?: XOR<UserScalarRelationFilter, UserWhereInput>
    board?: XOR<BoardScalarRelationFilter, BoardWhereInput>
    team?: XOR<TeamNullableScalarRelationFilter, TeamWhereInput> | null
    completedTiles?: CompletedTileListRelationFilter
  }

  export type PlayerBoardOrderByWithRelationInput = {
    id?: SortOrder
    userId?: SortOrder
    boardId?: SortOrder
    teamId?: SortOrderInput | SortOrder
    currentPosition?: SortOrder
    diceRollsToday?: SortOrder
    lastRollDate?: SortOrderInput | SortOrder
    createdAt?: SortOrder
    updatedAt?: SortOrder
    user?: UserOrderByWithRelationInput
    board?: BoardOrderByWithRelationInput
    team?: TeamOrderByWithRelationInput
    completedTiles?: CompletedTileOrderByRelationAggregateInput
  }

  export type PlayerBoardWhereUniqueInput = Prisma.AtLeast<{
    id?: string
    userId_boardId?: PlayerBoardUserIdBoardIdCompoundUniqueInput
    teamId_boardId?: PlayerBoardTeamIdBoardIdCompoundUniqueInput
    AND?: PlayerBoardWhereInput | PlayerBoardWhereInput[]
    OR?: PlayerBoardWhereInput[]
    NOT?: PlayerBoardWhereInput | PlayerBoardWhereInput[]
    userId?: UuidFilter<"PlayerBoard"> | string
    boardId?: UuidFilter<"PlayerBoard"> | string
    teamId?: UuidNullableFilter<"PlayerBoard"> | string | null
    currentPosition?: IntFilter<"PlayerBoard"> | number
    diceRollsToday?: IntFilter<"PlayerBoard"> | number
    lastRollDate?: DateTimeNullableFilter<"PlayerBoard"> | Date | string | null
    createdAt?: DateTimeFilter<"PlayerBoard"> | Date | string
    updatedAt?: DateTimeFilter<"PlayerBoard"> | Date | string
    user?: XOR<UserScalarRelationFilter, UserWhereInput>
    board?: XOR<BoardScalarRelationFilter, BoardWhereInput>
    team?: XOR<TeamNullableScalarRelationFilter, TeamWhereInput> | null
    completedTiles?: CompletedTileListRelationFilter
  }, "id" | "userId_boardId" | "teamId_boardId">

  export type PlayerBoardOrderByWithAggregationInput = {
    id?: SortOrder
    userId?: SortOrder
    boardId?: SortOrder
    teamId?: SortOrderInput | SortOrder
    currentPosition?: SortOrder
    diceRollsToday?: SortOrder
    lastRollDate?: SortOrderInput | SortOrder
    createdAt?: SortOrder
    updatedAt?: SortOrder
    _count?: PlayerBoardCountOrderByAggregateInput
    _avg?: PlayerBoardAvgOrderByAggregateInput
    _max?: PlayerBoardMaxOrderByAggregateInput
    _min?: PlayerBoardMinOrderByAggregateInput
    _sum?: PlayerBoardSumOrderByAggregateInput
  }

  export type PlayerBoardScalarWhereWithAggregatesInput = {
    AND?: PlayerBoardScalarWhereWithAggregatesInput | PlayerBoardScalarWhereWithAggregatesInput[]
    OR?: PlayerBoardScalarWhereWithAggregatesInput[]
    NOT?: PlayerBoardScalarWhereWithAggregatesInput | PlayerBoardScalarWhereWithAggregatesInput[]
    id?: UuidWithAggregatesFilter<"PlayerBoard"> | string
    userId?: UuidWithAggregatesFilter<"PlayerBoard"> | string
    boardId?: UuidWithAggregatesFilter<"PlayerBoard"> | string
    teamId?: UuidNullableWithAggregatesFilter<"PlayerBoard"> | string | null
    currentPosition?: IntWithAggregatesFilter<"PlayerBoard"> | number
    diceRollsToday?: IntWithAggregatesFilter<"PlayerBoard"> | number
    lastRollDate?: DateTimeNullableWithAggregatesFilter<"PlayerBoard"> | Date | string | null
    createdAt?: DateTimeWithAggregatesFilter<"PlayerBoard"> | Date | string
    updatedAt?: DateTimeWithAggregatesFilter<"PlayerBoard"> | Date | string
  }

  export type CompletedTileWhereInput = {
    AND?: CompletedTileWhereInput | CompletedTileWhereInput[]
    OR?: CompletedTileWhereInput[]
    NOT?: CompletedTileWhereInput | CompletedTileWhereInput[]
    id?: UuidFilter<"CompletedTile"> | string
    playerBoardId?: UuidFilter<"CompletedTile"> | string
    tileId?: UuidFilter<"CompletedTile"> | string
    completedAt?: DateTimeFilter<"CompletedTile"> | Date | string
    completedVia?: EnumCompletionSourceFilter<"CompletedTile"> | $Enums.CompletionSource
    playerBoard?: XOR<PlayerBoardScalarRelationFilter, PlayerBoardWhereInput>
    tile?: XOR<TileScalarRelationFilter, TileWhereInput>
  }

  export type CompletedTileOrderByWithRelationInput = {
    id?: SortOrder
    playerBoardId?: SortOrder
    tileId?: SortOrder
    completedAt?: SortOrder
    completedVia?: SortOrder
    playerBoard?: PlayerBoardOrderByWithRelationInput
    tile?: TileOrderByWithRelationInput
  }

  export type CompletedTileWhereUniqueInput = Prisma.AtLeast<{
    id?: string
    playerBoardId_tileId?: CompletedTilePlayerBoardIdTileIdCompoundUniqueInput
    AND?: CompletedTileWhereInput | CompletedTileWhereInput[]
    OR?: CompletedTileWhereInput[]
    NOT?: CompletedTileWhereInput | CompletedTileWhereInput[]
    playerBoardId?: UuidFilter<"CompletedTile"> | string
    tileId?: UuidFilter<"CompletedTile"> | string
    completedAt?: DateTimeFilter<"CompletedTile"> | Date | string
    completedVia?: EnumCompletionSourceFilter<"CompletedTile"> | $Enums.CompletionSource
    playerBoard?: XOR<PlayerBoardScalarRelationFilter, PlayerBoardWhereInput>
    tile?: XOR<TileScalarRelationFilter, TileWhereInput>
  }, "id" | "playerBoardId_tileId">

  export type CompletedTileOrderByWithAggregationInput = {
    id?: SortOrder
    playerBoardId?: SortOrder
    tileId?: SortOrder
    completedAt?: SortOrder
    completedVia?: SortOrder
    _count?: CompletedTileCountOrderByAggregateInput
    _max?: CompletedTileMaxOrderByAggregateInput
    _min?: CompletedTileMinOrderByAggregateInput
  }

  export type CompletedTileScalarWhereWithAggregatesInput = {
    AND?: CompletedTileScalarWhereWithAggregatesInput | CompletedTileScalarWhereWithAggregatesInput[]
    OR?: CompletedTileScalarWhereWithAggregatesInput[]
    NOT?: CompletedTileScalarWhereWithAggregatesInput | CompletedTileScalarWhereWithAggregatesInput[]
    id?: UuidWithAggregatesFilter<"CompletedTile"> | string
    playerBoardId?: UuidWithAggregatesFilter<"CompletedTile"> | string
    tileId?: UuidWithAggregatesFilter<"CompletedTile"> | string
    completedAt?: DateTimeWithAggregatesFilter<"CompletedTile"> | Date | string
    completedVia?: EnumCompletionSourceWithAggregatesFilter<"CompletedTile"> | $Enums.CompletionSource
  }

  export type TeamWhereInput = {
    AND?: TeamWhereInput | TeamWhereInput[]
    OR?: TeamWhereInput[]
    NOT?: TeamWhereInput | TeamWhereInput[]
    id?: UuidFilter<"Team"> | string
    name?: StringFilter<"Team"> | string
    iconUrl?: StringNullableFilter<"Team"> | string | null
    createdAt?: DateTimeFilter<"Team"> | Date | string
    updatedAt?: DateTimeFilter<"Team"> | Date | string
    members?: TeamMemberListRelationFilter
    boardTeams?: BoardTeamListRelationFilter
    playerBoards?: PlayerBoardListRelationFilter
  }

  export type TeamOrderByWithRelationInput = {
    id?: SortOrder
    name?: SortOrder
    iconUrl?: SortOrderInput | SortOrder
    createdAt?: SortOrder
    updatedAt?: SortOrder
    members?: TeamMemberOrderByRelationAggregateInput
    boardTeams?: BoardTeamOrderByRelationAggregateInput
    playerBoards?: PlayerBoardOrderByRelationAggregateInput
  }

  export type TeamWhereUniqueInput = Prisma.AtLeast<{
    id?: string
    AND?: TeamWhereInput | TeamWhereInput[]
    OR?: TeamWhereInput[]
    NOT?: TeamWhereInput | TeamWhereInput[]
    name?: StringFilter<"Team"> | string
    iconUrl?: StringNullableFilter<"Team"> | string | null
    createdAt?: DateTimeFilter<"Team"> | Date | string
    updatedAt?: DateTimeFilter<"Team"> | Date | string
    members?: TeamMemberListRelationFilter
    boardTeams?: BoardTeamListRelationFilter
    playerBoards?: PlayerBoardListRelationFilter
  }, "id">

  export type TeamOrderByWithAggregationInput = {
    id?: SortOrder
    name?: SortOrder
    iconUrl?: SortOrderInput | SortOrder
    createdAt?: SortOrder
    updatedAt?: SortOrder
    _count?: TeamCountOrderByAggregateInput
    _max?: TeamMaxOrderByAggregateInput
    _min?: TeamMinOrderByAggregateInput
  }

  export type TeamScalarWhereWithAggregatesInput = {
    AND?: TeamScalarWhereWithAggregatesInput | TeamScalarWhereWithAggregatesInput[]
    OR?: TeamScalarWhereWithAggregatesInput[]
    NOT?: TeamScalarWhereWithAggregatesInput | TeamScalarWhereWithAggregatesInput[]
    id?: UuidWithAggregatesFilter<"Team"> | string
    name?: StringWithAggregatesFilter<"Team"> | string
    iconUrl?: StringNullableWithAggregatesFilter<"Team"> | string | null
    createdAt?: DateTimeWithAggregatesFilter<"Team"> | Date | string
    updatedAt?: DateTimeWithAggregatesFilter<"Team"> | Date | string
  }

  export type TeamMemberWhereInput = {
    AND?: TeamMemberWhereInput | TeamMemberWhereInput[]
    OR?: TeamMemberWhereInput[]
    NOT?: TeamMemberWhereInput | TeamMemberWhereInput[]
    id?: UuidFilter<"TeamMember"> | string
    teamId?: UuidFilter<"TeamMember"> | string
    userId?: UuidFilter<"TeamMember"> | string
    createdAt?: DateTimeFilter<"TeamMember"> | Date | string
    team?: XOR<TeamScalarRelationFilter, TeamWhereInput>
    user?: XOR<UserScalarRelationFilter, UserWhereInput>
  }

  export type TeamMemberOrderByWithRelationInput = {
    id?: SortOrder
    teamId?: SortOrder
    userId?: SortOrder
    createdAt?: SortOrder
    team?: TeamOrderByWithRelationInput
    user?: UserOrderByWithRelationInput
  }

  export type TeamMemberWhereUniqueInput = Prisma.AtLeast<{
    id?: string
    teamId_userId?: TeamMemberTeamIdUserIdCompoundUniqueInput
    AND?: TeamMemberWhereInput | TeamMemberWhereInput[]
    OR?: TeamMemberWhereInput[]
    NOT?: TeamMemberWhereInput | TeamMemberWhereInput[]
    teamId?: UuidFilter<"TeamMember"> | string
    userId?: UuidFilter<"TeamMember"> | string
    createdAt?: DateTimeFilter<"TeamMember"> | Date | string
    team?: XOR<TeamScalarRelationFilter, TeamWhereInput>
    user?: XOR<UserScalarRelationFilter, UserWhereInput>
  }, "id" | "teamId_userId">

  export type TeamMemberOrderByWithAggregationInput = {
    id?: SortOrder
    teamId?: SortOrder
    userId?: SortOrder
    createdAt?: SortOrder
    _count?: TeamMemberCountOrderByAggregateInput
    _max?: TeamMemberMaxOrderByAggregateInput
    _min?: TeamMemberMinOrderByAggregateInput
  }

  export type TeamMemberScalarWhereWithAggregatesInput = {
    AND?: TeamMemberScalarWhereWithAggregatesInput | TeamMemberScalarWhereWithAggregatesInput[]
    OR?: TeamMemberScalarWhereWithAggregatesInput[]
    NOT?: TeamMemberScalarWhereWithAggregatesInput | TeamMemberScalarWhereWithAggregatesInput[]
    id?: UuidWithAggregatesFilter<"TeamMember"> | string
    teamId?: UuidWithAggregatesFilter<"TeamMember"> | string
    userId?: UuidWithAggregatesFilter<"TeamMember"> | string
    createdAt?: DateTimeWithAggregatesFilter<"TeamMember"> | Date | string
  }

  export type RoleCreateInput = {
    id?: string
    name: string
    description?: string | null
    createdAt?: Date | string
    updatedAt?: Date | string
    userRoles?: UserRoleCreateNestedManyWithoutRoleInput
  }

  export type RoleUncheckedCreateInput = {
    id?: string
    name: string
    description?: string | null
    createdAt?: Date | string
    updatedAt?: Date | string
    userRoles?: UserRoleUncheckedCreateNestedManyWithoutRoleInput
  }

  export type RoleUpdateInput = {
    id?: StringFieldUpdateOperationsInput | string
    name?: StringFieldUpdateOperationsInput | string
    description?: NullableStringFieldUpdateOperationsInput | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    userRoles?: UserRoleUpdateManyWithoutRoleNestedInput
  }

  export type RoleUncheckedUpdateInput = {
    id?: StringFieldUpdateOperationsInput | string
    name?: StringFieldUpdateOperationsInput | string
    description?: NullableStringFieldUpdateOperationsInput | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    userRoles?: UserRoleUncheckedUpdateManyWithoutRoleNestedInput
  }

  export type RoleCreateManyInput = {
    id?: string
    name: string
    description?: string | null
    createdAt?: Date | string
    updatedAt?: Date | string
  }

  export type RoleUpdateManyMutationInput = {
    id?: StringFieldUpdateOperationsInput | string
    name?: StringFieldUpdateOperationsInput | string
    description?: NullableStringFieldUpdateOperationsInput | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type RoleUncheckedUpdateManyInput = {
    id?: StringFieldUpdateOperationsInput | string
    name?: StringFieldUpdateOperationsInput | string
    description?: NullableStringFieldUpdateOperationsInput | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type UserCreateInput = {
    id?: string
    discordId: string
    discordUsername: string
    nickname?: string | null
    avatarUrl?: string | null
    createdAt?: Date | string
    updatedAt?: Date | string
    userRoles?: UserRoleCreateNestedManyWithoutUserInput
    userPermissions?: UserPermissionCreateNestedManyWithoutUserInput
    boardAuthors?: BoardAuthorCreateNestedManyWithoutUserInput
    playerBoards?: PlayerBoardCreateNestedManyWithoutUserInput
    teamMembers?: TeamMemberCreateNestedManyWithoutUserInput
  }

  export type UserUncheckedCreateInput = {
    id?: string
    discordId: string
    discordUsername: string
    nickname?: string | null
    avatarUrl?: string | null
    createdAt?: Date | string
    updatedAt?: Date | string
    userRoles?: UserRoleUncheckedCreateNestedManyWithoutUserInput
    userPermissions?: UserPermissionUncheckedCreateNestedManyWithoutUserInput
    boardAuthors?: BoardAuthorUncheckedCreateNestedManyWithoutUserInput
    playerBoards?: PlayerBoardUncheckedCreateNestedManyWithoutUserInput
    teamMembers?: TeamMemberUncheckedCreateNestedManyWithoutUserInput
  }

  export type UserUpdateInput = {
    id?: StringFieldUpdateOperationsInput | string
    discordId?: StringFieldUpdateOperationsInput | string
    discordUsername?: StringFieldUpdateOperationsInput | string
    nickname?: NullableStringFieldUpdateOperationsInput | string | null
    avatarUrl?: NullableStringFieldUpdateOperationsInput | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    userRoles?: UserRoleUpdateManyWithoutUserNestedInput
    userPermissions?: UserPermissionUpdateManyWithoutUserNestedInput
    boardAuthors?: BoardAuthorUpdateManyWithoutUserNestedInput
    playerBoards?: PlayerBoardUpdateManyWithoutUserNestedInput
    teamMembers?: TeamMemberUpdateManyWithoutUserNestedInput
  }

  export type UserUncheckedUpdateInput = {
    id?: StringFieldUpdateOperationsInput | string
    discordId?: StringFieldUpdateOperationsInput | string
    discordUsername?: StringFieldUpdateOperationsInput | string
    nickname?: NullableStringFieldUpdateOperationsInput | string | null
    avatarUrl?: NullableStringFieldUpdateOperationsInput | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    userRoles?: UserRoleUncheckedUpdateManyWithoutUserNestedInput
    userPermissions?: UserPermissionUncheckedUpdateManyWithoutUserNestedInput
    boardAuthors?: BoardAuthorUncheckedUpdateManyWithoutUserNestedInput
    playerBoards?: PlayerBoardUncheckedUpdateManyWithoutUserNestedInput
    teamMembers?: TeamMemberUncheckedUpdateManyWithoutUserNestedInput
  }

  export type UserCreateManyInput = {
    id?: string
    discordId: string
    discordUsername: string
    nickname?: string | null
    avatarUrl?: string | null
    createdAt?: Date | string
    updatedAt?: Date | string
  }

  export type UserUpdateManyMutationInput = {
    id?: StringFieldUpdateOperationsInput | string
    discordId?: StringFieldUpdateOperationsInput | string
    discordUsername?: StringFieldUpdateOperationsInput | string
    nickname?: NullableStringFieldUpdateOperationsInput | string | null
    avatarUrl?: NullableStringFieldUpdateOperationsInput | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type UserUncheckedUpdateManyInput = {
    id?: StringFieldUpdateOperationsInput | string
    discordId?: StringFieldUpdateOperationsInput | string
    discordUsername?: StringFieldUpdateOperationsInput | string
    nickname?: NullableStringFieldUpdateOperationsInput | string | null
    avatarUrl?: NullableStringFieldUpdateOperationsInput | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type UserPermissionCreateInput = {
    id?: string
    permissionKey: string
    createdAt?: Date | string
    user: UserCreateNestedOneWithoutUserPermissionsInput
  }

  export type UserPermissionUncheckedCreateInput = {
    id?: string
    userId: string
    permissionKey: string
    createdAt?: Date | string
  }

  export type UserPermissionUpdateInput = {
    id?: StringFieldUpdateOperationsInput | string
    permissionKey?: StringFieldUpdateOperationsInput | string
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    user?: UserUpdateOneRequiredWithoutUserPermissionsNestedInput
  }

  export type UserPermissionUncheckedUpdateInput = {
    id?: StringFieldUpdateOperationsInput | string
    userId?: StringFieldUpdateOperationsInput | string
    permissionKey?: StringFieldUpdateOperationsInput | string
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type UserPermissionCreateManyInput = {
    id?: string
    userId: string
    permissionKey: string
    createdAt?: Date | string
  }

  export type UserPermissionUpdateManyMutationInput = {
    id?: StringFieldUpdateOperationsInput | string
    permissionKey?: StringFieldUpdateOperationsInput | string
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type UserPermissionUncheckedUpdateManyInput = {
    id?: StringFieldUpdateOperationsInput | string
    userId?: StringFieldUpdateOperationsInput | string
    permissionKey?: StringFieldUpdateOperationsInput | string
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type UserRoleCreateInput = {
    id?: string
    createdAt?: Date | string
    user: UserCreateNestedOneWithoutUserRolesInput
    role: RoleCreateNestedOneWithoutUserRolesInput
  }

  export type UserRoleUncheckedCreateInput = {
    id?: string
    userId: string
    roleId: string
    createdAt?: Date | string
  }

  export type UserRoleUpdateInput = {
    id?: StringFieldUpdateOperationsInput | string
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    user?: UserUpdateOneRequiredWithoutUserRolesNestedInput
    role?: RoleUpdateOneRequiredWithoutUserRolesNestedInput
  }

  export type UserRoleUncheckedUpdateInput = {
    id?: StringFieldUpdateOperationsInput | string
    userId?: StringFieldUpdateOperationsInput | string
    roleId?: StringFieldUpdateOperationsInput | string
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type UserRoleCreateManyInput = {
    id?: string
    userId: string
    roleId: string
    createdAt?: Date | string
  }

  export type UserRoleUpdateManyMutationInput = {
    id?: StringFieldUpdateOperationsInput | string
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type UserRoleUncheckedUpdateManyInput = {
    id?: StringFieldUpdateOperationsInput | string
    userId?: StringFieldUpdateOperationsInput | string
    roleId?: StringFieldUpdateOperationsInput | string
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type TaskCreateInput = {
    id?: string
    title: string
    iconUrl?: string | null
    description?: string | null
    createdAt?: Date | string
    updatedAt?: Date | string
    tiles?: TileCreateNestedManyWithoutTaskInput
  }

  export type TaskUncheckedCreateInput = {
    id?: string
    title: string
    iconUrl?: string | null
    description?: string | null
    createdAt?: Date | string
    updatedAt?: Date | string
    tiles?: TileUncheckedCreateNestedManyWithoutTaskInput
  }

  export type TaskUpdateInput = {
    id?: StringFieldUpdateOperationsInput | string
    title?: StringFieldUpdateOperationsInput | string
    iconUrl?: NullableStringFieldUpdateOperationsInput | string | null
    description?: NullableStringFieldUpdateOperationsInput | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    tiles?: TileUpdateManyWithoutTaskNestedInput
  }

  export type TaskUncheckedUpdateInput = {
    id?: StringFieldUpdateOperationsInput | string
    title?: StringFieldUpdateOperationsInput | string
    iconUrl?: NullableStringFieldUpdateOperationsInput | string | null
    description?: NullableStringFieldUpdateOperationsInput | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    tiles?: TileUncheckedUpdateManyWithoutTaskNestedInput
  }

  export type TaskCreateManyInput = {
    id?: string
    title: string
    iconUrl?: string | null
    description?: string | null
    createdAt?: Date | string
    updatedAt?: Date | string
  }

  export type TaskUpdateManyMutationInput = {
    id?: StringFieldUpdateOperationsInput | string
    title?: StringFieldUpdateOperationsInput | string
    iconUrl?: NullableStringFieldUpdateOperationsInput | string | null
    description?: NullableStringFieldUpdateOperationsInput | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type TaskUncheckedUpdateManyInput = {
    id?: StringFieldUpdateOperationsInput | string
    title?: StringFieldUpdateOperationsInput | string
    iconUrl?: NullableStringFieldUpdateOperationsInput | string | null
    description?: NullableStringFieldUpdateOperationsInput | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type BoardCreateInput = {
    id?: string
    title: string
    description?: string | null
    startDate?: Date | string | null
    endDate?: Date | string | null
    size?: $Enums.BoardSize
    mode?: $Enums.BoardMode
    diceRollLimit?: number | null
    createdAt?: Date | string
    updatedAt?: Date | string
    authors?: BoardAuthorCreateNestedManyWithoutBoardInput
    tiles?: TileCreateNestedManyWithoutBoardInput
    playerBoards?: PlayerBoardCreateNestedManyWithoutBoardInput
    boardTeams?: BoardTeamCreateNestedManyWithoutBoardInput
  }

  export type BoardUncheckedCreateInput = {
    id?: string
    title: string
    description?: string | null
    startDate?: Date | string | null
    endDate?: Date | string | null
    size?: $Enums.BoardSize
    mode?: $Enums.BoardMode
    diceRollLimit?: number | null
    createdAt?: Date | string
    updatedAt?: Date | string
    authors?: BoardAuthorUncheckedCreateNestedManyWithoutBoardInput
    tiles?: TileUncheckedCreateNestedManyWithoutBoardInput
    playerBoards?: PlayerBoardUncheckedCreateNestedManyWithoutBoardInput
    boardTeams?: BoardTeamUncheckedCreateNestedManyWithoutBoardInput
  }

  export type BoardUpdateInput = {
    id?: StringFieldUpdateOperationsInput | string
    title?: StringFieldUpdateOperationsInput | string
    description?: NullableStringFieldUpdateOperationsInput | string | null
    startDate?: NullableDateTimeFieldUpdateOperationsInput | Date | string | null
    endDate?: NullableDateTimeFieldUpdateOperationsInput | Date | string | null
    size?: EnumBoardSizeFieldUpdateOperationsInput | $Enums.BoardSize
    mode?: EnumBoardModeFieldUpdateOperationsInput | $Enums.BoardMode
    diceRollLimit?: NullableIntFieldUpdateOperationsInput | number | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    authors?: BoardAuthorUpdateManyWithoutBoardNestedInput
    tiles?: TileUpdateManyWithoutBoardNestedInput
    playerBoards?: PlayerBoardUpdateManyWithoutBoardNestedInput
    boardTeams?: BoardTeamUpdateManyWithoutBoardNestedInput
  }

  export type BoardUncheckedUpdateInput = {
    id?: StringFieldUpdateOperationsInput | string
    title?: StringFieldUpdateOperationsInput | string
    description?: NullableStringFieldUpdateOperationsInput | string | null
    startDate?: NullableDateTimeFieldUpdateOperationsInput | Date | string | null
    endDate?: NullableDateTimeFieldUpdateOperationsInput | Date | string | null
    size?: EnumBoardSizeFieldUpdateOperationsInput | $Enums.BoardSize
    mode?: EnumBoardModeFieldUpdateOperationsInput | $Enums.BoardMode
    diceRollLimit?: NullableIntFieldUpdateOperationsInput | number | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    authors?: BoardAuthorUncheckedUpdateManyWithoutBoardNestedInput
    tiles?: TileUncheckedUpdateManyWithoutBoardNestedInput
    playerBoards?: PlayerBoardUncheckedUpdateManyWithoutBoardNestedInput
    boardTeams?: BoardTeamUncheckedUpdateManyWithoutBoardNestedInput
  }

  export type BoardCreateManyInput = {
    id?: string
    title: string
    description?: string | null
    startDate?: Date | string | null
    endDate?: Date | string | null
    size?: $Enums.BoardSize
    mode?: $Enums.BoardMode
    diceRollLimit?: number | null
    createdAt?: Date | string
    updatedAt?: Date | string
  }

  export type BoardUpdateManyMutationInput = {
    id?: StringFieldUpdateOperationsInput | string
    title?: StringFieldUpdateOperationsInput | string
    description?: NullableStringFieldUpdateOperationsInput | string | null
    startDate?: NullableDateTimeFieldUpdateOperationsInput | Date | string | null
    endDate?: NullableDateTimeFieldUpdateOperationsInput | Date | string | null
    size?: EnumBoardSizeFieldUpdateOperationsInput | $Enums.BoardSize
    mode?: EnumBoardModeFieldUpdateOperationsInput | $Enums.BoardMode
    diceRollLimit?: NullableIntFieldUpdateOperationsInput | number | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type BoardUncheckedUpdateManyInput = {
    id?: StringFieldUpdateOperationsInput | string
    title?: StringFieldUpdateOperationsInput | string
    description?: NullableStringFieldUpdateOperationsInput | string | null
    startDate?: NullableDateTimeFieldUpdateOperationsInput | Date | string | null
    endDate?: NullableDateTimeFieldUpdateOperationsInput | Date | string | null
    size?: EnumBoardSizeFieldUpdateOperationsInput | $Enums.BoardSize
    mode?: EnumBoardModeFieldUpdateOperationsInput | $Enums.BoardMode
    diceRollLimit?: NullableIntFieldUpdateOperationsInput | number | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type BoardTeamCreateInput = {
    id?: string
    createdAt?: Date | string
    board: BoardCreateNestedOneWithoutBoardTeamsInput
    team: TeamCreateNestedOneWithoutBoardTeamsInput
  }

  export type BoardTeamUncheckedCreateInput = {
    id?: string
    boardId: string
    teamId: string
    createdAt?: Date | string
  }

  export type BoardTeamUpdateInput = {
    id?: StringFieldUpdateOperationsInput | string
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    board?: BoardUpdateOneRequiredWithoutBoardTeamsNestedInput
    team?: TeamUpdateOneRequiredWithoutBoardTeamsNestedInput
  }

  export type BoardTeamUncheckedUpdateInput = {
    id?: StringFieldUpdateOperationsInput | string
    boardId?: StringFieldUpdateOperationsInput | string
    teamId?: StringFieldUpdateOperationsInput | string
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type BoardTeamCreateManyInput = {
    id?: string
    boardId: string
    teamId: string
    createdAt?: Date | string
  }

  export type BoardTeamUpdateManyMutationInput = {
    id?: StringFieldUpdateOperationsInput | string
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type BoardTeamUncheckedUpdateManyInput = {
    id?: StringFieldUpdateOperationsInput | string
    boardId?: StringFieldUpdateOperationsInput | string
    teamId?: StringFieldUpdateOperationsInput | string
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type BoardAuthorCreateInput = {
    id?: string
    isOwner?: boolean
    createdAt?: Date | string
    board: BoardCreateNestedOneWithoutAuthorsInput
    user: UserCreateNestedOneWithoutBoardAuthorsInput
  }

  export type BoardAuthorUncheckedCreateInput = {
    id?: string
    boardId: string
    userId: string
    isOwner?: boolean
    createdAt?: Date | string
  }

  export type BoardAuthorUpdateInput = {
    id?: StringFieldUpdateOperationsInput | string
    isOwner?: BoolFieldUpdateOperationsInput | boolean
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    board?: BoardUpdateOneRequiredWithoutAuthorsNestedInput
    user?: UserUpdateOneRequiredWithoutBoardAuthorsNestedInput
  }

  export type BoardAuthorUncheckedUpdateInput = {
    id?: StringFieldUpdateOperationsInput | string
    boardId?: StringFieldUpdateOperationsInput | string
    userId?: StringFieldUpdateOperationsInput | string
    isOwner?: BoolFieldUpdateOperationsInput | boolean
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type BoardAuthorCreateManyInput = {
    id?: string
    boardId: string
    userId: string
    isOwner?: boolean
    createdAt?: Date | string
  }

  export type BoardAuthorUpdateManyMutationInput = {
    id?: StringFieldUpdateOperationsInput | string
    isOwner?: BoolFieldUpdateOperationsInput | boolean
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type BoardAuthorUncheckedUpdateManyInput = {
    id?: StringFieldUpdateOperationsInput | string
    boardId?: StringFieldUpdateOperationsInput | string
    userId?: StringFieldUpdateOperationsInput | string
    isOwner?: BoolFieldUpdateOperationsInput | boolean
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type TileCreateInput = {
    id?: string
    position: number
    titleOverride?: string | null
    type?: $Enums.TileType
    targetPosition?: number | null
    createdAt?: Date | string
    updatedAt?: Date | string
    board: BoardCreateNestedOneWithoutTilesInput
    task?: TaskCreateNestedOneWithoutTilesInput
    completedTiles?: CompletedTileCreateNestedManyWithoutTileInput
  }

  export type TileUncheckedCreateInput = {
    id?: string
    boardId: string
    position: number
    taskId?: string | null
    titleOverride?: string | null
    type?: $Enums.TileType
    targetPosition?: number | null
    createdAt?: Date | string
    updatedAt?: Date | string
    completedTiles?: CompletedTileUncheckedCreateNestedManyWithoutTileInput
  }

  export type TileUpdateInput = {
    id?: StringFieldUpdateOperationsInput | string
    position?: IntFieldUpdateOperationsInput | number
    titleOverride?: NullableStringFieldUpdateOperationsInput | string | null
    type?: EnumTileTypeFieldUpdateOperationsInput | $Enums.TileType
    targetPosition?: NullableIntFieldUpdateOperationsInput | number | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    board?: BoardUpdateOneRequiredWithoutTilesNestedInput
    task?: TaskUpdateOneWithoutTilesNestedInput
    completedTiles?: CompletedTileUpdateManyWithoutTileNestedInput
  }

  export type TileUncheckedUpdateInput = {
    id?: StringFieldUpdateOperationsInput | string
    boardId?: StringFieldUpdateOperationsInput | string
    position?: IntFieldUpdateOperationsInput | number
    taskId?: NullableStringFieldUpdateOperationsInput | string | null
    titleOverride?: NullableStringFieldUpdateOperationsInput | string | null
    type?: EnumTileTypeFieldUpdateOperationsInput | $Enums.TileType
    targetPosition?: NullableIntFieldUpdateOperationsInput | number | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    completedTiles?: CompletedTileUncheckedUpdateManyWithoutTileNestedInput
  }

  export type TileCreateManyInput = {
    id?: string
    boardId: string
    position: number
    taskId?: string | null
    titleOverride?: string | null
    type?: $Enums.TileType
    targetPosition?: number | null
    createdAt?: Date | string
    updatedAt?: Date | string
  }

  export type TileUpdateManyMutationInput = {
    id?: StringFieldUpdateOperationsInput | string
    position?: IntFieldUpdateOperationsInput | number
    titleOverride?: NullableStringFieldUpdateOperationsInput | string | null
    type?: EnumTileTypeFieldUpdateOperationsInput | $Enums.TileType
    targetPosition?: NullableIntFieldUpdateOperationsInput | number | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type TileUncheckedUpdateManyInput = {
    id?: StringFieldUpdateOperationsInput | string
    boardId?: StringFieldUpdateOperationsInput | string
    position?: IntFieldUpdateOperationsInput | number
    taskId?: NullableStringFieldUpdateOperationsInput | string | null
    titleOverride?: NullableStringFieldUpdateOperationsInput | string | null
    type?: EnumTileTypeFieldUpdateOperationsInput | $Enums.TileType
    targetPosition?: NullableIntFieldUpdateOperationsInput | number | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type PlayerBoardCreateInput = {
    id?: string
    currentPosition?: number
    diceRollsToday?: number
    lastRollDate?: Date | string | null
    createdAt?: Date | string
    updatedAt?: Date | string
    user: UserCreateNestedOneWithoutPlayerBoardsInput
    board: BoardCreateNestedOneWithoutPlayerBoardsInput
    team?: TeamCreateNestedOneWithoutPlayerBoardsInput
    completedTiles?: CompletedTileCreateNestedManyWithoutPlayerBoardInput
  }

  export type PlayerBoardUncheckedCreateInput = {
    id?: string
    userId: string
    boardId: string
    teamId?: string | null
    currentPosition?: number
    diceRollsToday?: number
    lastRollDate?: Date | string | null
    createdAt?: Date | string
    updatedAt?: Date | string
    completedTiles?: CompletedTileUncheckedCreateNestedManyWithoutPlayerBoardInput
  }

  export type PlayerBoardUpdateInput = {
    id?: StringFieldUpdateOperationsInput | string
    currentPosition?: IntFieldUpdateOperationsInput | number
    diceRollsToday?: IntFieldUpdateOperationsInput | number
    lastRollDate?: NullableDateTimeFieldUpdateOperationsInput | Date | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    user?: UserUpdateOneRequiredWithoutPlayerBoardsNestedInput
    board?: BoardUpdateOneRequiredWithoutPlayerBoardsNestedInput
    team?: TeamUpdateOneWithoutPlayerBoardsNestedInput
    completedTiles?: CompletedTileUpdateManyWithoutPlayerBoardNestedInput
  }

  export type PlayerBoardUncheckedUpdateInput = {
    id?: StringFieldUpdateOperationsInput | string
    userId?: StringFieldUpdateOperationsInput | string
    boardId?: StringFieldUpdateOperationsInput | string
    teamId?: NullableStringFieldUpdateOperationsInput | string | null
    currentPosition?: IntFieldUpdateOperationsInput | number
    diceRollsToday?: IntFieldUpdateOperationsInput | number
    lastRollDate?: NullableDateTimeFieldUpdateOperationsInput | Date | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    completedTiles?: CompletedTileUncheckedUpdateManyWithoutPlayerBoardNestedInput
  }

  export type PlayerBoardCreateManyInput = {
    id?: string
    userId: string
    boardId: string
    teamId?: string | null
    currentPosition?: number
    diceRollsToday?: number
    lastRollDate?: Date | string | null
    createdAt?: Date | string
    updatedAt?: Date | string
  }

  export type PlayerBoardUpdateManyMutationInput = {
    id?: StringFieldUpdateOperationsInput | string
    currentPosition?: IntFieldUpdateOperationsInput | number
    diceRollsToday?: IntFieldUpdateOperationsInput | number
    lastRollDate?: NullableDateTimeFieldUpdateOperationsInput | Date | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type PlayerBoardUncheckedUpdateManyInput = {
    id?: StringFieldUpdateOperationsInput | string
    userId?: StringFieldUpdateOperationsInput | string
    boardId?: StringFieldUpdateOperationsInput | string
    teamId?: NullableStringFieldUpdateOperationsInput | string | null
    currentPosition?: IntFieldUpdateOperationsInput | number
    diceRollsToday?: IntFieldUpdateOperationsInput | number
    lastRollDate?: NullableDateTimeFieldUpdateOperationsInput | Date | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type CompletedTileCreateInput = {
    id?: string
    completedAt?: Date | string
    completedVia?: $Enums.CompletionSource
    playerBoard: PlayerBoardCreateNestedOneWithoutCompletedTilesInput
    tile: TileCreateNestedOneWithoutCompletedTilesInput
  }

  export type CompletedTileUncheckedCreateInput = {
    id?: string
    playerBoardId: string
    tileId: string
    completedAt?: Date | string
    completedVia?: $Enums.CompletionSource
  }

  export type CompletedTileUpdateInput = {
    id?: StringFieldUpdateOperationsInput | string
    completedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    completedVia?: EnumCompletionSourceFieldUpdateOperationsInput | $Enums.CompletionSource
    playerBoard?: PlayerBoardUpdateOneRequiredWithoutCompletedTilesNestedInput
    tile?: TileUpdateOneRequiredWithoutCompletedTilesNestedInput
  }

  export type CompletedTileUncheckedUpdateInput = {
    id?: StringFieldUpdateOperationsInput | string
    playerBoardId?: StringFieldUpdateOperationsInput | string
    tileId?: StringFieldUpdateOperationsInput | string
    completedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    completedVia?: EnumCompletionSourceFieldUpdateOperationsInput | $Enums.CompletionSource
  }

  export type CompletedTileCreateManyInput = {
    id?: string
    playerBoardId: string
    tileId: string
    completedAt?: Date | string
    completedVia?: $Enums.CompletionSource
  }

  export type CompletedTileUpdateManyMutationInput = {
    id?: StringFieldUpdateOperationsInput | string
    completedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    completedVia?: EnumCompletionSourceFieldUpdateOperationsInput | $Enums.CompletionSource
  }

  export type CompletedTileUncheckedUpdateManyInput = {
    id?: StringFieldUpdateOperationsInput | string
    playerBoardId?: StringFieldUpdateOperationsInput | string
    tileId?: StringFieldUpdateOperationsInput | string
    completedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    completedVia?: EnumCompletionSourceFieldUpdateOperationsInput | $Enums.CompletionSource
  }

  export type TeamCreateInput = {
    id?: string
    name: string
    iconUrl?: string | null
    createdAt?: Date | string
    updatedAt?: Date | string
    members?: TeamMemberCreateNestedManyWithoutTeamInput
    boardTeams?: BoardTeamCreateNestedManyWithoutTeamInput
    playerBoards?: PlayerBoardCreateNestedManyWithoutTeamInput
  }

  export type TeamUncheckedCreateInput = {
    id?: string
    name: string
    iconUrl?: string | null
    createdAt?: Date | string
    updatedAt?: Date | string
    members?: TeamMemberUncheckedCreateNestedManyWithoutTeamInput
    boardTeams?: BoardTeamUncheckedCreateNestedManyWithoutTeamInput
    playerBoards?: PlayerBoardUncheckedCreateNestedManyWithoutTeamInput
  }

  export type TeamUpdateInput = {
    id?: StringFieldUpdateOperationsInput | string
    name?: StringFieldUpdateOperationsInput | string
    iconUrl?: NullableStringFieldUpdateOperationsInput | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    members?: TeamMemberUpdateManyWithoutTeamNestedInput
    boardTeams?: BoardTeamUpdateManyWithoutTeamNestedInput
    playerBoards?: PlayerBoardUpdateManyWithoutTeamNestedInput
  }

  export type TeamUncheckedUpdateInput = {
    id?: StringFieldUpdateOperationsInput | string
    name?: StringFieldUpdateOperationsInput | string
    iconUrl?: NullableStringFieldUpdateOperationsInput | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    members?: TeamMemberUncheckedUpdateManyWithoutTeamNestedInput
    boardTeams?: BoardTeamUncheckedUpdateManyWithoutTeamNestedInput
    playerBoards?: PlayerBoardUncheckedUpdateManyWithoutTeamNestedInput
  }

  export type TeamCreateManyInput = {
    id?: string
    name: string
    iconUrl?: string | null
    createdAt?: Date | string
    updatedAt?: Date | string
  }

  export type TeamUpdateManyMutationInput = {
    id?: StringFieldUpdateOperationsInput | string
    name?: StringFieldUpdateOperationsInput | string
    iconUrl?: NullableStringFieldUpdateOperationsInput | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type TeamUncheckedUpdateManyInput = {
    id?: StringFieldUpdateOperationsInput | string
    name?: StringFieldUpdateOperationsInput | string
    iconUrl?: NullableStringFieldUpdateOperationsInput | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type TeamMemberCreateInput = {
    id?: string
    createdAt?: Date | string
    team: TeamCreateNestedOneWithoutMembersInput
    user: UserCreateNestedOneWithoutTeamMembersInput
  }

  export type TeamMemberUncheckedCreateInput = {
    id?: string
    teamId: string
    userId: string
    createdAt?: Date | string
  }

  export type TeamMemberUpdateInput = {
    id?: StringFieldUpdateOperationsInput | string
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    team?: TeamUpdateOneRequiredWithoutMembersNestedInput
    user?: UserUpdateOneRequiredWithoutTeamMembersNestedInput
  }

  export type TeamMemberUncheckedUpdateInput = {
    id?: StringFieldUpdateOperationsInput | string
    teamId?: StringFieldUpdateOperationsInput | string
    userId?: StringFieldUpdateOperationsInput | string
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type TeamMemberCreateManyInput = {
    id?: string
    teamId: string
    userId: string
    createdAt?: Date | string
  }

  export type TeamMemberUpdateManyMutationInput = {
    id?: StringFieldUpdateOperationsInput | string
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type TeamMemberUncheckedUpdateManyInput = {
    id?: StringFieldUpdateOperationsInput | string
    teamId?: StringFieldUpdateOperationsInput | string
    userId?: StringFieldUpdateOperationsInput | string
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type UuidFilter<$PrismaModel = never> = {
    equals?: string | StringFieldRefInput<$PrismaModel>
    in?: string[] | ListStringFieldRefInput<$PrismaModel>
    notIn?: string[] | ListStringFieldRefInput<$PrismaModel>
    lt?: string | StringFieldRefInput<$PrismaModel>
    lte?: string | StringFieldRefInput<$PrismaModel>
    gt?: string | StringFieldRefInput<$PrismaModel>
    gte?: string | StringFieldRefInput<$PrismaModel>
    mode?: QueryMode
    not?: NestedUuidFilter<$PrismaModel> | string
  }

  export type StringFilter<$PrismaModel = never> = {
    equals?: string | StringFieldRefInput<$PrismaModel>
    in?: string[] | ListStringFieldRefInput<$PrismaModel>
    notIn?: string[] | ListStringFieldRefInput<$PrismaModel>
    lt?: string | StringFieldRefInput<$PrismaModel>
    lte?: string | StringFieldRefInput<$PrismaModel>
    gt?: string | StringFieldRefInput<$PrismaModel>
    gte?: string | StringFieldRefInput<$PrismaModel>
    contains?: string | StringFieldRefInput<$PrismaModel>
    startsWith?: string | StringFieldRefInput<$PrismaModel>
    endsWith?: string | StringFieldRefInput<$PrismaModel>
    mode?: QueryMode
    not?: NestedStringFilter<$PrismaModel> | string
  }

  export type StringNullableFilter<$PrismaModel = never> = {
    equals?: string | StringFieldRefInput<$PrismaModel> | null
    in?: string[] | ListStringFieldRefInput<$PrismaModel> | null
    notIn?: string[] | ListStringFieldRefInput<$PrismaModel> | null
    lt?: string | StringFieldRefInput<$PrismaModel>
    lte?: string | StringFieldRefInput<$PrismaModel>
    gt?: string | StringFieldRefInput<$PrismaModel>
    gte?: string | StringFieldRefInput<$PrismaModel>
    contains?: string | StringFieldRefInput<$PrismaModel>
    startsWith?: string | StringFieldRefInput<$PrismaModel>
    endsWith?: string | StringFieldRefInput<$PrismaModel>
    mode?: QueryMode
    not?: NestedStringNullableFilter<$PrismaModel> | string | null
  }

  export type DateTimeFilter<$PrismaModel = never> = {
    equals?: Date | string | DateTimeFieldRefInput<$PrismaModel>
    in?: Date[] | string[] | ListDateTimeFieldRefInput<$PrismaModel>
    notIn?: Date[] | string[] | ListDateTimeFieldRefInput<$PrismaModel>
    lt?: Date | string | DateTimeFieldRefInput<$PrismaModel>
    lte?: Date | string | DateTimeFieldRefInput<$PrismaModel>
    gt?: Date | string | DateTimeFieldRefInput<$PrismaModel>
    gte?: Date | string | DateTimeFieldRefInput<$PrismaModel>
    not?: NestedDateTimeFilter<$PrismaModel> | Date | string
  }

  export type UserRoleListRelationFilter = {
    every?: UserRoleWhereInput
    some?: UserRoleWhereInput
    none?: UserRoleWhereInput
  }

  export type SortOrderInput = {
    sort: SortOrder
    nulls?: NullsOrder
  }

  export type UserRoleOrderByRelationAggregateInput = {
    _count?: SortOrder
  }

  export type RoleCountOrderByAggregateInput = {
    id?: SortOrder
    name?: SortOrder
    description?: SortOrder
    createdAt?: SortOrder
    updatedAt?: SortOrder
  }

  export type RoleMaxOrderByAggregateInput = {
    id?: SortOrder
    name?: SortOrder
    description?: SortOrder
    createdAt?: SortOrder
    updatedAt?: SortOrder
  }

  export type RoleMinOrderByAggregateInput = {
    id?: SortOrder
    name?: SortOrder
    description?: SortOrder
    createdAt?: SortOrder
    updatedAt?: SortOrder
  }

  export type UuidWithAggregatesFilter<$PrismaModel = never> = {
    equals?: string | StringFieldRefInput<$PrismaModel>
    in?: string[] | ListStringFieldRefInput<$PrismaModel>
    notIn?: string[] | ListStringFieldRefInput<$PrismaModel>
    lt?: string | StringFieldRefInput<$PrismaModel>
    lte?: string | StringFieldRefInput<$PrismaModel>
    gt?: string | StringFieldRefInput<$PrismaModel>
    gte?: string | StringFieldRefInput<$PrismaModel>
    mode?: QueryMode
    not?: NestedUuidWithAggregatesFilter<$PrismaModel> | string
    _count?: NestedIntFilter<$PrismaModel>
    _min?: NestedStringFilter<$PrismaModel>
    _max?: NestedStringFilter<$PrismaModel>
  }

  export type StringWithAggregatesFilter<$PrismaModel = never> = {
    equals?: string | StringFieldRefInput<$PrismaModel>
    in?: string[] | ListStringFieldRefInput<$PrismaModel>
    notIn?: string[] | ListStringFieldRefInput<$PrismaModel>
    lt?: string | StringFieldRefInput<$PrismaModel>
    lte?: string | StringFieldRefInput<$PrismaModel>
    gt?: string | StringFieldRefInput<$PrismaModel>
    gte?: string | StringFieldRefInput<$PrismaModel>
    contains?: string | StringFieldRefInput<$PrismaModel>
    startsWith?: string | StringFieldRefInput<$PrismaModel>
    endsWith?: string | StringFieldRefInput<$PrismaModel>
    mode?: QueryMode
    not?: NestedStringWithAggregatesFilter<$PrismaModel> | string
    _count?: NestedIntFilter<$PrismaModel>
    _min?: NestedStringFilter<$PrismaModel>
    _max?: NestedStringFilter<$PrismaModel>
  }

  export type StringNullableWithAggregatesFilter<$PrismaModel = never> = {
    equals?: string | StringFieldRefInput<$PrismaModel> | null
    in?: string[] | ListStringFieldRefInput<$PrismaModel> | null
    notIn?: string[] | ListStringFieldRefInput<$PrismaModel> | null
    lt?: string | StringFieldRefInput<$PrismaModel>
    lte?: string | StringFieldRefInput<$PrismaModel>
    gt?: string | StringFieldRefInput<$PrismaModel>
    gte?: string | StringFieldRefInput<$PrismaModel>
    contains?: string | StringFieldRefInput<$PrismaModel>
    startsWith?: string | StringFieldRefInput<$PrismaModel>
    endsWith?: string | StringFieldRefInput<$PrismaModel>
    mode?: QueryMode
    not?: NestedStringNullableWithAggregatesFilter<$PrismaModel> | string | null
    _count?: NestedIntNullableFilter<$PrismaModel>
    _min?: NestedStringNullableFilter<$PrismaModel>
    _max?: NestedStringNullableFilter<$PrismaModel>
  }

  export type DateTimeWithAggregatesFilter<$PrismaModel = never> = {
    equals?: Date | string | DateTimeFieldRefInput<$PrismaModel>
    in?: Date[] | string[] | ListDateTimeFieldRefInput<$PrismaModel>
    notIn?: Date[] | string[] | ListDateTimeFieldRefInput<$PrismaModel>
    lt?: Date | string | DateTimeFieldRefInput<$PrismaModel>
    lte?: Date | string | DateTimeFieldRefInput<$PrismaModel>
    gt?: Date | string | DateTimeFieldRefInput<$PrismaModel>
    gte?: Date | string | DateTimeFieldRefInput<$PrismaModel>
    not?: NestedDateTimeWithAggregatesFilter<$PrismaModel> | Date | string
    _count?: NestedIntFilter<$PrismaModel>
    _min?: NestedDateTimeFilter<$PrismaModel>
    _max?: NestedDateTimeFilter<$PrismaModel>
  }

  export type UserPermissionListRelationFilter = {
    every?: UserPermissionWhereInput
    some?: UserPermissionWhereInput
    none?: UserPermissionWhereInput
  }

  export type BoardAuthorListRelationFilter = {
    every?: BoardAuthorWhereInput
    some?: BoardAuthorWhereInput
    none?: BoardAuthorWhereInput
  }

  export type PlayerBoardListRelationFilter = {
    every?: PlayerBoardWhereInput
    some?: PlayerBoardWhereInput
    none?: PlayerBoardWhereInput
  }

  export type TeamMemberListRelationFilter = {
    every?: TeamMemberWhereInput
    some?: TeamMemberWhereInput
    none?: TeamMemberWhereInput
  }

  export type UserPermissionOrderByRelationAggregateInput = {
    _count?: SortOrder
  }

  export type BoardAuthorOrderByRelationAggregateInput = {
    _count?: SortOrder
  }

  export type PlayerBoardOrderByRelationAggregateInput = {
    _count?: SortOrder
  }

  export type TeamMemberOrderByRelationAggregateInput = {
    _count?: SortOrder
  }

  export type UserCountOrderByAggregateInput = {
    id?: SortOrder
    discordId?: SortOrder
    discordUsername?: SortOrder
    nickname?: SortOrder
    avatarUrl?: SortOrder
    createdAt?: SortOrder
    updatedAt?: SortOrder
  }

  export type UserMaxOrderByAggregateInput = {
    id?: SortOrder
    discordId?: SortOrder
    discordUsername?: SortOrder
    nickname?: SortOrder
    avatarUrl?: SortOrder
    createdAt?: SortOrder
    updatedAt?: SortOrder
  }

  export type UserMinOrderByAggregateInput = {
    id?: SortOrder
    discordId?: SortOrder
    discordUsername?: SortOrder
    nickname?: SortOrder
    avatarUrl?: SortOrder
    createdAt?: SortOrder
    updatedAt?: SortOrder
  }

  export type UserScalarRelationFilter = {
    is?: UserWhereInput
    isNot?: UserWhereInput
  }

  export type UserPermissionUserIdPermissionKeyCompoundUniqueInput = {
    userId: string
    permissionKey: string
  }

  export type UserPermissionCountOrderByAggregateInput = {
    id?: SortOrder
    userId?: SortOrder
    permissionKey?: SortOrder
    createdAt?: SortOrder
  }

  export type UserPermissionMaxOrderByAggregateInput = {
    id?: SortOrder
    userId?: SortOrder
    permissionKey?: SortOrder
    createdAt?: SortOrder
  }

  export type UserPermissionMinOrderByAggregateInput = {
    id?: SortOrder
    userId?: SortOrder
    permissionKey?: SortOrder
    createdAt?: SortOrder
  }

  export type RoleScalarRelationFilter = {
    is?: RoleWhereInput
    isNot?: RoleWhereInput
  }

  export type UserRoleUserIdRoleIdCompoundUniqueInput = {
    userId: string
    roleId: string
  }

  export type UserRoleCountOrderByAggregateInput = {
    id?: SortOrder
    userId?: SortOrder
    roleId?: SortOrder
    createdAt?: SortOrder
  }

  export type UserRoleMaxOrderByAggregateInput = {
    id?: SortOrder
    userId?: SortOrder
    roleId?: SortOrder
    createdAt?: SortOrder
  }

  export type UserRoleMinOrderByAggregateInput = {
    id?: SortOrder
    userId?: SortOrder
    roleId?: SortOrder
    createdAt?: SortOrder
  }

  export type TileListRelationFilter = {
    every?: TileWhereInput
    some?: TileWhereInput
    none?: TileWhereInput
  }

  export type TileOrderByRelationAggregateInput = {
    _count?: SortOrder
  }

  export type TaskCountOrderByAggregateInput = {
    id?: SortOrder
    title?: SortOrder
    iconUrl?: SortOrder
    description?: SortOrder
    createdAt?: SortOrder
    updatedAt?: SortOrder
  }

  export type TaskMaxOrderByAggregateInput = {
    id?: SortOrder
    title?: SortOrder
    iconUrl?: SortOrder
    description?: SortOrder
    createdAt?: SortOrder
    updatedAt?: SortOrder
  }

  export type TaskMinOrderByAggregateInput = {
    id?: SortOrder
    title?: SortOrder
    iconUrl?: SortOrder
    description?: SortOrder
    createdAt?: SortOrder
    updatedAt?: SortOrder
  }

  export type DateTimeNullableFilter<$PrismaModel = never> = {
    equals?: Date | string | DateTimeFieldRefInput<$PrismaModel> | null
    in?: Date[] | string[] | ListDateTimeFieldRefInput<$PrismaModel> | null
    notIn?: Date[] | string[] | ListDateTimeFieldRefInput<$PrismaModel> | null
    lt?: Date | string | DateTimeFieldRefInput<$PrismaModel>
    lte?: Date | string | DateTimeFieldRefInput<$PrismaModel>
    gt?: Date | string | DateTimeFieldRefInput<$PrismaModel>
    gte?: Date | string | DateTimeFieldRefInput<$PrismaModel>
    not?: NestedDateTimeNullableFilter<$PrismaModel> | Date | string | null
  }

  export type EnumBoardSizeFilter<$PrismaModel = never> = {
    equals?: $Enums.BoardSize | EnumBoardSizeFieldRefInput<$PrismaModel>
    in?: $Enums.BoardSize[] | ListEnumBoardSizeFieldRefInput<$PrismaModel>
    notIn?: $Enums.BoardSize[] | ListEnumBoardSizeFieldRefInput<$PrismaModel>
    not?: NestedEnumBoardSizeFilter<$PrismaModel> | $Enums.BoardSize
  }

  export type EnumBoardModeFilter<$PrismaModel = never> = {
    equals?: $Enums.BoardMode | EnumBoardModeFieldRefInput<$PrismaModel>
    in?: $Enums.BoardMode[] | ListEnumBoardModeFieldRefInput<$PrismaModel>
    notIn?: $Enums.BoardMode[] | ListEnumBoardModeFieldRefInput<$PrismaModel>
    not?: NestedEnumBoardModeFilter<$PrismaModel> | $Enums.BoardMode
  }

  export type IntNullableFilter<$PrismaModel = never> = {
    equals?: number | IntFieldRefInput<$PrismaModel> | null
    in?: number[] | ListIntFieldRefInput<$PrismaModel> | null
    notIn?: number[] | ListIntFieldRefInput<$PrismaModel> | null
    lt?: number | IntFieldRefInput<$PrismaModel>
    lte?: number | IntFieldRefInput<$PrismaModel>
    gt?: number | IntFieldRefInput<$PrismaModel>
    gte?: number | IntFieldRefInput<$PrismaModel>
    not?: NestedIntNullableFilter<$PrismaModel> | number | null
  }

  export type BoardTeamListRelationFilter = {
    every?: BoardTeamWhereInput
    some?: BoardTeamWhereInput
    none?: BoardTeamWhereInput
  }

  export type BoardTeamOrderByRelationAggregateInput = {
    _count?: SortOrder
  }

  export type BoardCountOrderByAggregateInput = {
    id?: SortOrder
    title?: SortOrder
    description?: SortOrder
    startDate?: SortOrder
    endDate?: SortOrder
    size?: SortOrder
    mode?: SortOrder
    diceRollLimit?: SortOrder
    createdAt?: SortOrder
    updatedAt?: SortOrder
  }

  export type BoardAvgOrderByAggregateInput = {
    diceRollLimit?: SortOrder
  }

  export type BoardMaxOrderByAggregateInput = {
    id?: SortOrder
    title?: SortOrder
    description?: SortOrder
    startDate?: SortOrder
    endDate?: SortOrder
    size?: SortOrder
    mode?: SortOrder
    diceRollLimit?: SortOrder
    createdAt?: SortOrder
    updatedAt?: SortOrder
  }

  export type BoardMinOrderByAggregateInput = {
    id?: SortOrder
    title?: SortOrder
    description?: SortOrder
    startDate?: SortOrder
    endDate?: SortOrder
    size?: SortOrder
    mode?: SortOrder
    diceRollLimit?: SortOrder
    createdAt?: SortOrder
    updatedAt?: SortOrder
  }

  export type BoardSumOrderByAggregateInput = {
    diceRollLimit?: SortOrder
  }

  export type DateTimeNullableWithAggregatesFilter<$PrismaModel = never> = {
    equals?: Date | string | DateTimeFieldRefInput<$PrismaModel> | null
    in?: Date[] | string[] | ListDateTimeFieldRefInput<$PrismaModel> | null
    notIn?: Date[] | string[] | ListDateTimeFieldRefInput<$PrismaModel> | null
    lt?: Date | string | DateTimeFieldRefInput<$PrismaModel>
    lte?: Date | string | DateTimeFieldRefInput<$PrismaModel>
    gt?: Date | string | DateTimeFieldRefInput<$PrismaModel>
    gte?: Date | string | DateTimeFieldRefInput<$PrismaModel>
    not?: NestedDateTimeNullableWithAggregatesFilter<$PrismaModel> | Date | string | null
    _count?: NestedIntNullableFilter<$PrismaModel>
    _min?: NestedDateTimeNullableFilter<$PrismaModel>
    _max?: NestedDateTimeNullableFilter<$PrismaModel>
  }

  export type EnumBoardSizeWithAggregatesFilter<$PrismaModel = never> = {
    equals?: $Enums.BoardSize | EnumBoardSizeFieldRefInput<$PrismaModel>
    in?: $Enums.BoardSize[] | ListEnumBoardSizeFieldRefInput<$PrismaModel>
    notIn?: $Enums.BoardSize[] | ListEnumBoardSizeFieldRefInput<$PrismaModel>
    not?: NestedEnumBoardSizeWithAggregatesFilter<$PrismaModel> | $Enums.BoardSize
    _count?: NestedIntFilter<$PrismaModel>
    _min?: NestedEnumBoardSizeFilter<$PrismaModel>
    _max?: NestedEnumBoardSizeFilter<$PrismaModel>
  }

  export type EnumBoardModeWithAggregatesFilter<$PrismaModel = never> = {
    equals?: $Enums.BoardMode | EnumBoardModeFieldRefInput<$PrismaModel>
    in?: $Enums.BoardMode[] | ListEnumBoardModeFieldRefInput<$PrismaModel>
    notIn?: $Enums.BoardMode[] | ListEnumBoardModeFieldRefInput<$PrismaModel>
    not?: NestedEnumBoardModeWithAggregatesFilter<$PrismaModel> | $Enums.BoardMode
    _count?: NestedIntFilter<$PrismaModel>
    _min?: NestedEnumBoardModeFilter<$PrismaModel>
    _max?: NestedEnumBoardModeFilter<$PrismaModel>
  }

  export type IntNullableWithAggregatesFilter<$PrismaModel = never> = {
    equals?: number | IntFieldRefInput<$PrismaModel> | null
    in?: number[] | ListIntFieldRefInput<$PrismaModel> | null
    notIn?: number[] | ListIntFieldRefInput<$PrismaModel> | null
    lt?: number | IntFieldRefInput<$PrismaModel>
    lte?: number | IntFieldRefInput<$PrismaModel>
    gt?: number | IntFieldRefInput<$PrismaModel>
    gte?: number | IntFieldRefInput<$PrismaModel>
    not?: NestedIntNullableWithAggregatesFilter<$PrismaModel> | number | null
    _count?: NestedIntNullableFilter<$PrismaModel>
    _avg?: NestedFloatNullableFilter<$PrismaModel>
    _sum?: NestedIntNullableFilter<$PrismaModel>
    _min?: NestedIntNullableFilter<$PrismaModel>
    _max?: NestedIntNullableFilter<$PrismaModel>
  }

  export type BoardScalarRelationFilter = {
    is?: BoardWhereInput
    isNot?: BoardWhereInput
  }

  export type TeamScalarRelationFilter = {
    is?: TeamWhereInput
    isNot?: TeamWhereInput
  }

  export type BoardTeamBoardIdTeamIdCompoundUniqueInput = {
    boardId: string
    teamId: string
  }

  export type BoardTeamCountOrderByAggregateInput = {
    id?: SortOrder
    boardId?: SortOrder
    teamId?: SortOrder
    createdAt?: SortOrder
  }

  export type BoardTeamMaxOrderByAggregateInput = {
    id?: SortOrder
    boardId?: SortOrder
    teamId?: SortOrder
    createdAt?: SortOrder
  }

  export type BoardTeamMinOrderByAggregateInput = {
    id?: SortOrder
    boardId?: SortOrder
    teamId?: SortOrder
    createdAt?: SortOrder
  }

  export type BoolFilter<$PrismaModel = never> = {
    equals?: boolean | BooleanFieldRefInput<$PrismaModel>
    not?: NestedBoolFilter<$PrismaModel> | boolean
  }

  export type BoardAuthorBoardIdUserIdCompoundUniqueInput = {
    boardId: string
    userId: string
  }

  export type BoardAuthorCountOrderByAggregateInput = {
    id?: SortOrder
    boardId?: SortOrder
    userId?: SortOrder
    isOwner?: SortOrder
    createdAt?: SortOrder
  }

  export type BoardAuthorMaxOrderByAggregateInput = {
    id?: SortOrder
    boardId?: SortOrder
    userId?: SortOrder
    isOwner?: SortOrder
    createdAt?: SortOrder
  }

  export type BoardAuthorMinOrderByAggregateInput = {
    id?: SortOrder
    boardId?: SortOrder
    userId?: SortOrder
    isOwner?: SortOrder
    createdAt?: SortOrder
  }

  export type BoolWithAggregatesFilter<$PrismaModel = never> = {
    equals?: boolean | BooleanFieldRefInput<$PrismaModel>
    not?: NestedBoolWithAggregatesFilter<$PrismaModel> | boolean
    _count?: NestedIntFilter<$PrismaModel>
    _min?: NestedBoolFilter<$PrismaModel>
    _max?: NestedBoolFilter<$PrismaModel>
  }

  export type IntFilter<$PrismaModel = never> = {
    equals?: number | IntFieldRefInput<$PrismaModel>
    in?: number[] | ListIntFieldRefInput<$PrismaModel>
    notIn?: number[] | ListIntFieldRefInput<$PrismaModel>
    lt?: number | IntFieldRefInput<$PrismaModel>
    lte?: number | IntFieldRefInput<$PrismaModel>
    gt?: number | IntFieldRefInput<$PrismaModel>
    gte?: number | IntFieldRefInput<$PrismaModel>
    not?: NestedIntFilter<$PrismaModel> | number
  }

  export type UuidNullableFilter<$PrismaModel = never> = {
    equals?: string | StringFieldRefInput<$PrismaModel> | null
    in?: string[] | ListStringFieldRefInput<$PrismaModel> | null
    notIn?: string[] | ListStringFieldRefInput<$PrismaModel> | null
    lt?: string | StringFieldRefInput<$PrismaModel>
    lte?: string | StringFieldRefInput<$PrismaModel>
    gt?: string | StringFieldRefInput<$PrismaModel>
    gte?: string | StringFieldRefInput<$PrismaModel>
    mode?: QueryMode
    not?: NestedUuidNullableFilter<$PrismaModel> | string | null
  }

  export type EnumTileTypeFilter<$PrismaModel = never> = {
    equals?: $Enums.TileType | EnumTileTypeFieldRefInput<$PrismaModel>
    in?: $Enums.TileType[] | ListEnumTileTypeFieldRefInput<$PrismaModel>
    notIn?: $Enums.TileType[] | ListEnumTileTypeFieldRefInput<$PrismaModel>
    not?: NestedEnumTileTypeFilter<$PrismaModel> | $Enums.TileType
  }

  export type TaskNullableScalarRelationFilter = {
    is?: TaskWhereInput | null
    isNot?: TaskWhereInput | null
  }

  export type CompletedTileListRelationFilter = {
    every?: CompletedTileWhereInput
    some?: CompletedTileWhereInput
    none?: CompletedTileWhereInput
  }

  export type CompletedTileOrderByRelationAggregateInput = {
    _count?: SortOrder
  }

  export type TileBoardIdPositionCompoundUniqueInput = {
    boardId: string
    position: number
  }

  export type TileCountOrderByAggregateInput = {
    id?: SortOrder
    boardId?: SortOrder
    position?: SortOrder
    taskId?: SortOrder
    titleOverride?: SortOrder
    type?: SortOrder
    targetPosition?: SortOrder
    createdAt?: SortOrder
    updatedAt?: SortOrder
  }

  export type TileAvgOrderByAggregateInput = {
    position?: SortOrder
    targetPosition?: SortOrder
  }

  export type TileMaxOrderByAggregateInput = {
    id?: SortOrder
    boardId?: SortOrder
    position?: SortOrder
    taskId?: SortOrder
    titleOverride?: SortOrder
    type?: SortOrder
    targetPosition?: SortOrder
    createdAt?: SortOrder
    updatedAt?: SortOrder
  }

  export type TileMinOrderByAggregateInput = {
    id?: SortOrder
    boardId?: SortOrder
    position?: SortOrder
    taskId?: SortOrder
    titleOverride?: SortOrder
    type?: SortOrder
    targetPosition?: SortOrder
    createdAt?: SortOrder
    updatedAt?: SortOrder
  }

  export type TileSumOrderByAggregateInput = {
    position?: SortOrder
    targetPosition?: SortOrder
  }

  export type IntWithAggregatesFilter<$PrismaModel = never> = {
    equals?: number | IntFieldRefInput<$PrismaModel>
    in?: number[] | ListIntFieldRefInput<$PrismaModel>
    notIn?: number[] | ListIntFieldRefInput<$PrismaModel>
    lt?: number | IntFieldRefInput<$PrismaModel>
    lte?: number | IntFieldRefInput<$PrismaModel>
    gt?: number | IntFieldRefInput<$PrismaModel>
    gte?: number | IntFieldRefInput<$PrismaModel>
    not?: NestedIntWithAggregatesFilter<$PrismaModel> | number
    _count?: NestedIntFilter<$PrismaModel>
    _avg?: NestedFloatFilter<$PrismaModel>
    _sum?: NestedIntFilter<$PrismaModel>
    _min?: NestedIntFilter<$PrismaModel>
    _max?: NestedIntFilter<$PrismaModel>
  }

  export type UuidNullableWithAggregatesFilter<$PrismaModel = never> = {
    equals?: string | StringFieldRefInput<$PrismaModel> | null
    in?: string[] | ListStringFieldRefInput<$PrismaModel> | null
    notIn?: string[] | ListStringFieldRefInput<$PrismaModel> | null
    lt?: string | StringFieldRefInput<$PrismaModel>
    lte?: string | StringFieldRefInput<$PrismaModel>
    gt?: string | StringFieldRefInput<$PrismaModel>
    gte?: string | StringFieldRefInput<$PrismaModel>
    mode?: QueryMode
    not?: NestedUuidNullableWithAggregatesFilter<$PrismaModel> | string | null
    _count?: NestedIntNullableFilter<$PrismaModel>
    _min?: NestedStringNullableFilter<$PrismaModel>
    _max?: NestedStringNullableFilter<$PrismaModel>
  }

  export type EnumTileTypeWithAggregatesFilter<$PrismaModel = never> = {
    equals?: $Enums.TileType | EnumTileTypeFieldRefInput<$PrismaModel>
    in?: $Enums.TileType[] | ListEnumTileTypeFieldRefInput<$PrismaModel>
    notIn?: $Enums.TileType[] | ListEnumTileTypeFieldRefInput<$PrismaModel>
    not?: NestedEnumTileTypeWithAggregatesFilter<$PrismaModel> | $Enums.TileType
    _count?: NestedIntFilter<$PrismaModel>
    _min?: NestedEnumTileTypeFilter<$PrismaModel>
    _max?: NestedEnumTileTypeFilter<$PrismaModel>
  }

  export type TeamNullableScalarRelationFilter = {
    is?: TeamWhereInput | null
    isNot?: TeamWhereInput | null
  }

  export type PlayerBoardUserIdBoardIdCompoundUniqueInput = {
    userId: string
    boardId: string
  }

  export type PlayerBoardTeamIdBoardIdCompoundUniqueInput = {
    teamId: string
    boardId: string
  }

  export type PlayerBoardCountOrderByAggregateInput = {
    id?: SortOrder
    userId?: SortOrder
    boardId?: SortOrder
    teamId?: SortOrder
    currentPosition?: SortOrder
    diceRollsToday?: SortOrder
    lastRollDate?: SortOrder
    createdAt?: SortOrder
    updatedAt?: SortOrder
  }

  export type PlayerBoardAvgOrderByAggregateInput = {
    currentPosition?: SortOrder
    diceRollsToday?: SortOrder
  }

  export type PlayerBoardMaxOrderByAggregateInput = {
    id?: SortOrder
    userId?: SortOrder
    boardId?: SortOrder
    teamId?: SortOrder
    currentPosition?: SortOrder
    diceRollsToday?: SortOrder
    lastRollDate?: SortOrder
    createdAt?: SortOrder
    updatedAt?: SortOrder
  }

  export type PlayerBoardMinOrderByAggregateInput = {
    id?: SortOrder
    userId?: SortOrder
    boardId?: SortOrder
    teamId?: SortOrder
    currentPosition?: SortOrder
    diceRollsToday?: SortOrder
    lastRollDate?: SortOrder
    createdAt?: SortOrder
    updatedAt?: SortOrder
  }

  export type PlayerBoardSumOrderByAggregateInput = {
    currentPosition?: SortOrder
    diceRollsToday?: SortOrder
  }

  export type EnumCompletionSourceFilter<$PrismaModel = never> = {
    equals?: $Enums.CompletionSource | EnumCompletionSourceFieldRefInput<$PrismaModel>
    in?: $Enums.CompletionSource[] | ListEnumCompletionSourceFieldRefInput<$PrismaModel>
    notIn?: $Enums.CompletionSource[] | ListEnumCompletionSourceFieldRefInput<$PrismaModel>
    not?: NestedEnumCompletionSourceFilter<$PrismaModel> | $Enums.CompletionSource
  }

  export type PlayerBoardScalarRelationFilter = {
    is?: PlayerBoardWhereInput
    isNot?: PlayerBoardWhereInput
  }

  export type TileScalarRelationFilter = {
    is?: TileWhereInput
    isNot?: TileWhereInput
  }

  export type CompletedTilePlayerBoardIdTileIdCompoundUniqueInput = {
    playerBoardId: string
    tileId: string
  }

  export type CompletedTileCountOrderByAggregateInput = {
    id?: SortOrder
    playerBoardId?: SortOrder
    tileId?: SortOrder
    completedAt?: SortOrder
    completedVia?: SortOrder
  }

  export type CompletedTileMaxOrderByAggregateInput = {
    id?: SortOrder
    playerBoardId?: SortOrder
    tileId?: SortOrder
    completedAt?: SortOrder
    completedVia?: SortOrder
  }

  export type CompletedTileMinOrderByAggregateInput = {
    id?: SortOrder
    playerBoardId?: SortOrder
    tileId?: SortOrder
    completedAt?: SortOrder
    completedVia?: SortOrder
  }

  export type EnumCompletionSourceWithAggregatesFilter<$PrismaModel = never> = {
    equals?: $Enums.CompletionSource | EnumCompletionSourceFieldRefInput<$PrismaModel>
    in?: $Enums.CompletionSource[] | ListEnumCompletionSourceFieldRefInput<$PrismaModel>
    notIn?: $Enums.CompletionSource[] | ListEnumCompletionSourceFieldRefInput<$PrismaModel>
    not?: NestedEnumCompletionSourceWithAggregatesFilter<$PrismaModel> | $Enums.CompletionSource
    _count?: NestedIntFilter<$PrismaModel>
    _min?: NestedEnumCompletionSourceFilter<$PrismaModel>
    _max?: NestedEnumCompletionSourceFilter<$PrismaModel>
  }

  export type TeamCountOrderByAggregateInput = {
    id?: SortOrder
    name?: SortOrder
    iconUrl?: SortOrder
    createdAt?: SortOrder
    updatedAt?: SortOrder
  }

  export type TeamMaxOrderByAggregateInput = {
    id?: SortOrder
    name?: SortOrder
    iconUrl?: SortOrder
    createdAt?: SortOrder
    updatedAt?: SortOrder
  }

  export type TeamMinOrderByAggregateInput = {
    id?: SortOrder
    name?: SortOrder
    iconUrl?: SortOrder
    createdAt?: SortOrder
    updatedAt?: SortOrder
  }

  export type TeamMemberTeamIdUserIdCompoundUniqueInput = {
    teamId: string
    userId: string
  }

  export type TeamMemberCountOrderByAggregateInput = {
    id?: SortOrder
    teamId?: SortOrder
    userId?: SortOrder
    createdAt?: SortOrder
  }

  export type TeamMemberMaxOrderByAggregateInput = {
    id?: SortOrder
    teamId?: SortOrder
    userId?: SortOrder
    createdAt?: SortOrder
  }

  export type TeamMemberMinOrderByAggregateInput = {
    id?: SortOrder
    teamId?: SortOrder
    userId?: SortOrder
    createdAt?: SortOrder
  }

  export type UserRoleCreateNestedManyWithoutRoleInput = {
    create?: XOR<UserRoleCreateWithoutRoleInput, UserRoleUncheckedCreateWithoutRoleInput> | UserRoleCreateWithoutRoleInput[] | UserRoleUncheckedCreateWithoutRoleInput[]
    connectOrCreate?: UserRoleCreateOrConnectWithoutRoleInput | UserRoleCreateOrConnectWithoutRoleInput[]
    createMany?: UserRoleCreateManyRoleInputEnvelope
    connect?: UserRoleWhereUniqueInput | UserRoleWhereUniqueInput[]
  }

  export type UserRoleUncheckedCreateNestedManyWithoutRoleInput = {
    create?: XOR<UserRoleCreateWithoutRoleInput, UserRoleUncheckedCreateWithoutRoleInput> | UserRoleCreateWithoutRoleInput[] | UserRoleUncheckedCreateWithoutRoleInput[]
    connectOrCreate?: UserRoleCreateOrConnectWithoutRoleInput | UserRoleCreateOrConnectWithoutRoleInput[]
    createMany?: UserRoleCreateManyRoleInputEnvelope
    connect?: UserRoleWhereUniqueInput | UserRoleWhereUniqueInput[]
  }

  export type StringFieldUpdateOperationsInput = {
    set?: string
  }

  export type NullableStringFieldUpdateOperationsInput = {
    set?: string | null
  }

  export type DateTimeFieldUpdateOperationsInput = {
    set?: Date | string
  }

  export type UserRoleUpdateManyWithoutRoleNestedInput = {
    create?: XOR<UserRoleCreateWithoutRoleInput, UserRoleUncheckedCreateWithoutRoleInput> | UserRoleCreateWithoutRoleInput[] | UserRoleUncheckedCreateWithoutRoleInput[]
    connectOrCreate?: UserRoleCreateOrConnectWithoutRoleInput | UserRoleCreateOrConnectWithoutRoleInput[]
    upsert?: UserRoleUpsertWithWhereUniqueWithoutRoleInput | UserRoleUpsertWithWhereUniqueWithoutRoleInput[]
    createMany?: UserRoleCreateManyRoleInputEnvelope
    set?: UserRoleWhereUniqueInput | UserRoleWhereUniqueInput[]
    disconnect?: UserRoleWhereUniqueInput | UserRoleWhereUniqueInput[]
    delete?: UserRoleWhereUniqueInput | UserRoleWhereUniqueInput[]
    connect?: UserRoleWhereUniqueInput | UserRoleWhereUniqueInput[]
    update?: UserRoleUpdateWithWhereUniqueWithoutRoleInput | UserRoleUpdateWithWhereUniqueWithoutRoleInput[]
    updateMany?: UserRoleUpdateManyWithWhereWithoutRoleInput | UserRoleUpdateManyWithWhereWithoutRoleInput[]
    deleteMany?: UserRoleScalarWhereInput | UserRoleScalarWhereInput[]
  }

  export type UserRoleUncheckedUpdateManyWithoutRoleNestedInput = {
    create?: XOR<UserRoleCreateWithoutRoleInput, UserRoleUncheckedCreateWithoutRoleInput> | UserRoleCreateWithoutRoleInput[] | UserRoleUncheckedCreateWithoutRoleInput[]
    connectOrCreate?: UserRoleCreateOrConnectWithoutRoleInput | UserRoleCreateOrConnectWithoutRoleInput[]
    upsert?: UserRoleUpsertWithWhereUniqueWithoutRoleInput | UserRoleUpsertWithWhereUniqueWithoutRoleInput[]
    createMany?: UserRoleCreateManyRoleInputEnvelope
    set?: UserRoleWhereUniqueInput | UserRoleWhereUniqueInput[]
    disconnect?: UserRoleWhereUniqueInput | UserRoleWhereUniqueInput[]
    delete?: UserRoleWhereUniqueInput | UserRoleWhereUniqueInput[]
    connect?: UserRoleWhereUniqueInput | UserRoleWhereUniqueInput[]
    update?: UserRoleUpdateWithWhereUniqueWithoutRoleInput | UserRoleUpdateWithWhereUniqueWithoutRoleInput[]
    updateMany?: UserRoleUpdateManyWithWhereWithoutRoleInput | UserRoleUpdateManyWithWhereWithoutRoleInput[]
    deleteMany?: UserRoleScalarWhereInput | UserRoleScalarWhereInput[]
  }

  export type UserRoleCreateNestedManyWithoutUserInput = {
    create?: XOR<UserRoleCreateWithoutUserInput, UserRoleUncheckedCreateWithoutUserInput> | UserRoleCreateWithoutUserInput[] | UserRoleUncheckedCreateWithoutUserInput[]
    connectOrCreate?: UserRoleCreateOrConnectWithoutUserInput | UserRoleCreateOrConnectWithoutUserInput[]
    createMany?: UserRoleCreateManyUserInputEnvelope
    connect?: UserRoleWhereUniqueInput | UserRoleWhereUniqueInput[]
  }

  export type UserPermissionCreateNestedManyWithoutUserInput = {
    create?: XOR<UserPermissionCreateWithoutUserInput, UserPermissionUncheckedCreateWithoutUserInput> | UserPermissionCreateWithoutUserInput[] | UserPermissionUncheckedCreateWithoutUserInput[]
    connectOrCreate?: UserPermissionCreateOrConnectWithoutUserInput | UserPermissionCreateOrConnectWithoutUserInput[]
    createMany?: UserPermissionCreateManyUserInputEnvelope
    connect?: UserPermissionWhereUniqueInput | UserPermissionWhereUniqueInput[]
  }

  export type BoardAuthorCreateNestedManyWithoutUserInput = {
    create?: XOR<BoardAuthorCreateWithoutUserInput, BoardAuthorUncheckedCreateWithoutUserInput> | BoardAuthorCreateWithoutUserInput[] | BoardAuthorUncheckedCreateWithoutUserInput[]
    connectOrCreate?: BoardAuthorCreateOrConnectWithoutUserInput | BoardAuthorCreateOrConnectWithoutUserInput[]
    createMany?: BoardAuthorCreateManyUserInputEnvelope
    connect?: BoardAuthorWhereUniqueInput | BoardAuthorWhereUniqueInput[]
  }

  export type PlayerBoardCreateNestedManyWithoutUserInput = {
    create?: XOR<PlayerBoardCreateWithoutUserInput, PlayerBoardUncheckedCreateWithoutUserInput> | PlayerBoardCreateWithoutUserInput[] | PlayerBoardUncheckedCreateWithoutUserInput[]
    connectOrCreate?: PlayerBoardCreateOrConnectWithoutUserInput | PlayerBoardCreateOrConnectWithoutUserInput[]
    createMany?: PlayerBoardCreateManyUserInputEnvelope
    connect?: PlayerBoardWhereUniqueInput | PlayerBoardWhereUniqueInput[]
  }

  export type TeamMemberCreateNestedManyWithoutUserInput = {
    create?: XOR<TeamMemberCreateWithoutUserInput, TeamMemberUncheckedCreateWithoutUserInput> | TeamMemberCreateWithoutUserInput[] | TeamMemberUncheckedCreateWithoutUserInput[]
    connectOrCreate?: TeamMemberCreateOrConnectWithoutUserInput | TeamMemberCreateOrConnectWithoutUserInput[]
    createMany?: TeamMemberCreateManyUserInputEnvelope
    connect?: TeamMemberWhereUniqueInput | TeamMemberWhereUniqueInput[]
  }

  export type UserRoleUncheckedCreateNestedManyWithoutUserInput = {
    create?: XOR<UserRoleCreateWithoutUserInput, UserRoleUncheckedCreateWithoutUserInput> | UserRoleCreateWithoutUserInput[] | UserRoleUncheckedCreateWithoutUserInput[]
    connectOrCreate?: UserRoleCreateOrConnectWithoutUserInput | UserRoleCreateOrConnectWithoutUserInput[]
    createMany?: UserRoleCreateManyUserInputEnvelope
    connect?: UserRoleWhereUniqueInput | UserRoleWhereUniqueInput[]
  }

  export type UserPermissionUncheckedCreateNestedManyWithoutUserInput = {
    create?: XOR<UserPermissionCreateWithoutUserInput, UserPermissionUncheckedCreateWithoutUserInput> | UserPermissionCreateWithoutUserInput[] | UserPermissionUncheckedCreateWithoutUserInput[]
    connectOrCreate?: UserPermissionCreateOrConnectWithoutUserInput | UserPermissionCreateOrConnectWithoutUserInput[]
    createMany?: UserPermissionCreateManyUserInputEnvelope
    connect?: UserPermissionWhereUniqueInput | UserPermissionWhereUniqueInput[]
  }

  export type BoardAuthorUncheckedCreateNestedManyWithoutUserInput = {
    create?: XOR<BoardAuthorCreateWithoutUserInput, BoardAuthorUncheckedCreateWithoutUserInput> | BoardAuthorCreateWithoutUserInput[] | BoardAuthorUncheckedCreateWithoutUserInput[]
    connectOrCreate?: BoardAuthorCreateOrConnectWithoutUserInput | BoardAuthorCreateOrConnectWithoutUserInput[]
    createMany?: BoardAuthorCreateManyUserInputEnvelope
    connect?: BoardAuthorWhereUniqueInput | BoardAuthorWhereUniqueInput[]
  }

  export type PlayerBoardUncheckedCreateNestedManyWithoutUserInput = {
    create?: XOR<PlayerBoardCreateWithoutUserInput, PlayerBoardUncheckedCreateWithoutUserInput> | PlayerBoardCreateWithoutUserInput[] | PlayerBoardUncheckedCreateWithoutUserInput[]
    connectOrCreate?: PlayerBoardCreateOrConnectWithoutUserInput | PlayerBoardCreateOrConnectWithoutUserInput[]
    createMany?: PlayerBoardCreateManyUserInputEnvelope
    connect?: PlayerBoardWhereUniqueInput | PlayerBoardWhereUniqueInput[]
  }

  export type TeamMemberUncheckedCreateNestedManyWithoutUserInput = {
    create?: XOR<TeamMemberCreateWithoutUserInput, TeamMemberUncheckedCreateWithoutUserInput> | TeamMemberCreateWithoutUserInput[] | TeamMemberUncheckedCreateWithoutUserInput[]
    connectOrCreate?: TeamMemberCreateOrConnectWithoutUserInput | TeamMemberCreateOrConnectWithoutUserInput[]
    createMany?: TeamMemberCreateManyUserInputEnvelope
    connect?: TeamMemberWhereUniqueInput | TeamMemberWhereUniqueInput[]
  }

  export type UserRoleUpdateManyWithoutUserNestedInput = {
    create?: XOR<UserRoleCreateWithoutUserInput, UserRoleUncheckedCreateWithoutUserInput> | UserRoleCreateWithoutUserInput[] | UserRoleUncheckedCreateWithoutUserInput[]
    connectOrCreate?: UserRoleCreateOrConnectWithoutUserInput | UserRoleCreateOrConnectWithoutUserInput[]
    upsert?: UserRoleUpsertWithWhereUniqueWithoutUserInput | UserRoleUpsertWithWhereUniqueWithoutUserInput[]
    createMany?: UserRoleCreateManyUserInputEnvelope
    set?: UserRoleWhereUniqueInput | UserRoleWhereUniqueInput[]
    disconnect?: UserRoleWhereUniqueInput | UserRoleWhereUniqueInput[]
    delete?: UserRoleWhereUniqueInput | UserRoleWhereUniqueInput[]
    connect?: UserRoleWhereUniqueInput | UserRoleWhereUniqueInput[]
    update?: UserRoleUpdateWithWhereUniqueWithoutUserInput | UserRoleUpdateWithWhereUniqueWithoutUserInput[]
    updateMany?: UserRoleUpdateManyWithWhereWithoutUserInput | UserRoleUpdateManyWithWhereWithoutUserInput[]
    deleteMany?: UserRoleScalarWhereInput | UserRoleScalarWhereInput[]
  }

  export type UserPermissionUpdateManyWithoutUserNestedInput = {
    create?: XOR<UserPermissionCreateWithoutUserInput, UserPermissionUncheckedCreateWithoutUserInput> | UserPermissionCreateWithoutUserInput[] | UserPermissionUncheckedCreateWithoutUserInput[]
    connectOrCreate?: UserPermissionCreateOrConnectWithoutUserInput | UserPermissionCreateOrConnectWithoutUserInput[]
    upsert?: UserPermissionUpsertWithWhereUniqueWithoutUserInput | UserPermissionUpsertWithWhereUniqueWithoutUserInput[]
    createMany?: UserPermissionCreateManyUserInputEnvelope
    set?: UserPermissionWhereUniqueInput | UserPermissionWhereUniqueInput[]
    disconnect?: UserPermissionWhereUniqueInput | UserPermissionWhereUniqueInput[]
    delete?: UserPermissionWhereUniqueInput | UserPermissionWhereUniqueInput[]
    connect?: UserPermissionWhereUniqueInput | UserPermissionWhereUniqueInput[]
    update?: UserPermissionUpdateWithWhereUniqueWithoutUserInput | UserPermissionUpdateWithWhereUniqueWithoutUserInput[]
    updateMany?: UserPermissionUpdateManyWithWhereWithoutUserInput | UserPermissionUpdateManyWithWhereWithoutUserInput[]
    deleteMany?: UserPermissionScalarWhereInput | UserPermissionScalarWhereInput[]
  }

  export type BoardAuthorUpdateManyWithoutUserNestedInput = {
    create?: XOR<BoardAuthorCreateWithoutUserInput, BoardAuthorUncheckedCreateWithoutUserInput> | BoardAuthorCreateWithoutUserInput[] | BoardAuthorUncheckedCreateWithoutUserInput[]
    connectOrCreate?: BoardAuthorCreateOrConnectWithoutUserInput | BoardAuthorCreateOrConnectWithoutUserInput[]
    upsert?: BoardAuthorUpsertWithWhereUniqueWithoutUserInput | BoardAuthorUpsertWithWhereUniqueWithoutUserInput[]
    createMany?: BoardAuthorCreateManyUserInputEnvelope
    set?: BoardAuthorWhereUniqueInput | BoardAuthorWhereUniqueInput[]
    disconnect?: BoardAuthorWhereUniqueInput | BoardAuthorWhereUniqueInput[]
    delete?: BoardAuthorWhereUniqueInput | BoardAuthorWhereUniqueInput[]
    connect?: BoardAuthorWhereUniqueInput | BoardAuthorWhereUniqueInput[]
    update?: BoardAuthorUpdateWithWhereUniqueWithoutUserInput | BoardAuthorUpdateWithWhereUniqueWithoutUserInput[]
    updateMany?: BoardAuthorUpdateManyWithWhereWithoutUserInput | BoardAuthorUpdateManyWithWhereWithoutUserInput[]
    deleteMany?: BoardAuthorScalarWhereInput | BoardAuthorScalarWhereInput[]
  }

  export type PlayerBoardUpdateManyWithoutUserNestedInput = {
    create?: XOR<PlayerBoardCreateWithoutUserInput, PlayerBoardUncheckedCreateWithoutUserInput> | PlayerBoardCreateWithoutUserInput[] | PlayerBoardUncheckedCreateWithoutUserInput[]
    connectOrCreate?: PlayerBoardCreateOrConnectWithoutUserInput | PlayerBoardCreateOrConnectWithoutUserInput[]
    upsert?: PlayerBoardUpsertWithWhereUniqueWithoutUserInput | PlayerBoardUpsertWithWhereUniqueWithoutUserInput[]
    createMany?: PlayerBoardCreateManyUserInputEnvelope
    set?: PlayerBoardWhereUniqueInput | PlayerBoardWhereUniqueInput[]
    disconnect?: PlayerBoardWhereUniqueInput | PlayerBoardWhereUniqueInput[]
    delete?: PlayerBoardWhereUniqueInput | PlayerBoardWhereUniqueInput[]
    connect?: PlayerBoardWhereUniqueInput | PlayerBoardWhereUniqueInput[]
    update?: PlayerBoardUpdateWithWhereUniqueWithoutUserInput | PlayerBoardUpdateWithWhereUniqueWithoutUserInput[]
    updateMany?: PlayerBoardUpdateManyWithWhereWithoutUserInput | PlayerBoardUpdateManyWithWhereWithoutUserInput[]
    deleteMany?: PlayerBoardScalarWhereInput | PlayerBoardScalarWhereInput[]
  }

  export type TeamMemberUpdateManyWithoutUserNestedInput = {
    create?: XOR<TeamMemberCreateWithoutUserInput, TeamMemberUncheckedCreateWithoutUserInput> | TeamMemberCreateWithoutUserInput[] | TeamMemberUncheckedCreateWithoutUserInput[]
    connectOrCreate?: TeamMemberCreateOrConnectWithoutUserInput | TeamMemberCreateOrConnectWithoutUserInput[]
    upsert?: TeamMemberUpsertWithWhereUniqueWithoutUserInput | TeamMemberUpsertWithWhereUniqueWithoutUserInput[]
    createMany?: TeamMemberCreateManyUserInputEnvelope
    set?: TeamMemberWhereUniqueInput | TeamMemberWhereUniqueInput[]
    disconnect?: TeamMemberWhereUniqueInput | TeamMemberWhereUniqueInput[]
    delete?: TeamMemberWhereUniqueInput | TeamMemberWhereUniqueInput[]
    connect?: TeamMemberWhereUniqueInput | TeamMemberWhereUniqueInput[]
    update?: TeamMemberUpdateWithWhereUniqueWithoutUserInput | TeamMemberUpdateWithWhereUniqueWithoutUserInput[]
    updateMany?: TeamMemberUpdateManyWithWhereWithoutUserInput | TeamMemberUpdateManyWithWhereWithoutUserInput[]
    deleteMany?: TeamMemberScalarWhereInput | TeamMemberScalarWhereInput[]
  }

  export type UserRoleUncheckedUpdateManyWithoutUserNestedInput = {
    create?: XOR<UserRoleCreateWithoutUserInput, UserRoleUncheckedCreateWithoutUserInput> | UserRoleCreateWithoutUserInput[] | UserRoleUncheckedCreateWithoutUserInput[]
    connectOrCreate?: UserRoleCreateOrConnectWithoutUserInput | UserRoleCreateOrConnectWithoutUserInput[]
    upsert?: UserRoleUpsertWithWhereUniqueWithoutUserInput | UserRoleUpsertWithWhereUniqueWithoutUserInput[]
    createMany?: UserRoleCreateManyUserInputEnvelope
    set?: UserRoleWhereUniqueInput | UserRoleWhereUniqueInput[]
    disconnect?: UserRoleWhereUniqueInput | UserRoleWhereUniqueInput[]
    delete?: UserRoleWhereUniqueInput | UserRoleWhereUniqueInput[]
    connect?: UserRoleWhereUniqueInput | UserRoleWhereUniqueInput[]
    update?: UserRoleUpdateWithWhereUniqueWithoutUserInput | UserRoleUpdateWithWhereUniqueWithoutUserInput[]
    updateMany?: UserRoleUpdateManyWithWhereWithoutUserInput | UserRoleUpdateManyWithWhereWithoutUserInput[]
    deleteMany?: UserRoleScalarWhereInput | UserRoleScalarWhereInput[]
  }

  export type UserPermissionUncheckedUpdateManyWithoutUserNestedInput = {
    create?: XOR<UserPermissionCreateWithoutUserInput, UserPermissionUncheckedCreateWithoutUserInput> | UserPermissionCreateWithoutUserInput[] | UserPermissionUncheckedCreateWithoutUserInput[]
    connectOrCreate?: UserPermissionCreateOrConnectWithoutUserInput | UserPermissionCreateOrConnectWithoutUserInput[]
    upsert?: UserPermissionUpsertWithWhereUniqueWithoutUserInput | UserPermissionUpsertWithWhereUniqueWithoutUserInput[]
    createMany?: UserPermissionCreateManyUserInputEnvelope
    set?: UserPermissionWhereUniqueInput | UserPermissionWhereUniqueInput[]
    disconnect?: UserPermissionWhereUniqueInput | UserPermissionWhereUniqueInput[]
    delete?: UserPermissionWhereUniqueInput | UserPermissionWhereUniqueInput[]
    connect?: UserPermissionWhereUniqueInput | UserPermissionWhereUniqueInput[]
    update?: UserPermissionUpdateWithWhereUniqueWithoutUserInput | UserPermissionUpdateWithWhereUniqueWithoutUserInput[]
    updateMany?: UserPermissionUpdateManyWithWhereWithoutUserInput | UserPermissionUpdateManyWithWhereWithoutUserInput[]
    deleteMany?: UserPermissionScalarWhereInput | UserPermissionScalarWhereInput[]
  }

  export type BoardAuthorUncheckedUpdateManyWithoutUserNestedInput = {
    create?: XOR<BoardAuthorCreateWithoutUserInput, BoardAuthorUncheckedCreateWithoutUserInput> | BoardAuthorCreateWithoutUserInput[] | BoardAuthorUncheckedCreateWithoutUserInput[]
    connectOrCreate?: BoardAuthorCreateOrConnectWithoutUserInput | BoardAuthorCreateOrConnectWithoutUserInput[]
    upsert?: BoardAuthorUpsertWithWhereUniqueWithoutUserInput | BoardAuthorUpsertWithWhereUniqueWithoutUserInput[]
    createMany?: BoardAuthorCreateManyUserInputEnvelope
    set?: BoardAuthorWhereUniqueInput | BoardAuthorWhereUniqueInput[]
    disconnect?: BoardAuthorWhereUniqueInput | BoardAuthorWhereUniqueInput[]
    delete?: BoardAuthorWhereUniqueInput | BoardAuthorWhereUniqueInput[]
    connect?: BoardAuthorWhereUniqueInput | BoardAuthorWhereUniqueInput[]
    update?: BoardAuthorUpdateWithWhereUniqueWithoutUserInput | BoardAuthorUpdateWithWhereUniqueWithoutUserInput[]
    updateMany?: BoardAuthorUpdateManyWithWhereWithoutUserInput | BoardAuthorUpdateManyWithWhereWithoutUserInput[]
    deleteMany?: BoardAuthorScalarWhereInput | BoardAuthorScalarWhereInput[]
  }

  export type PlayerBoardUncheckedUpdateManyWithoutUserNestedInput = {
    create?: XOR<PlayerBoardCreateWithoutUserInput, PlayerBoardUncheckedCreateWithoutUserInput> | PlayerBoardCreateWithoutUserInput[] | PlayerBoardUncheckedCreateWithoutUserInput[]
    connectOrCreate?: PlayerBoardCreateOrConnectWithoutUserInput | PlayerBoardCreateOrConnectWithoutUserInput[]
    upsert?: PlayerBoardUpsertWithWhereUniqueWithoutUserInput | PlayerBoardUpsertWithWhereUniqueWithoutUserInput[]
    createMany?: PlayerBoardCreateManyUserInputEnvelope
    set?: PlayerBoardWhereUniqueInput | PlayerBoardWhereUniqueInput[]
    disconnect?: PlayerBoardWhereUniqueInput | PlayerBoardWhereUniqueInput[]
    delete?: PlayerBoardWhereUniqueInput | PlayerBoardWhereUniqueInput[]
    connect?: PlayerBoardWhereUniqueInput | PlayerBoardWhereUniqueInput[]
    update?: PlayerBoardUpdateWithWhereUniqueWithoutUserInput | PlayerBoardUpdateWithWhereUniqueWithoutUserInput[]
    updateMany?: PlayerBoardUpdateManyWithWhereWithoutUserInput | PlayerBoardUpdateManyWithWhereWithoutUserInput[]
    deleteMany?: PlayerBoardScalarWhereInput | PlayerBoardScalarWhereInput[]
  }

  export type TeamMemberUncheckedUpdateManyWithoutUserNestedInput = {
    create?: XOR<TeamMemberCreateWithoutUserInput, TeamMemberUncheckedCreateWithoutUserInput> | TeamMemberCreateWithoutUserInput[] | TeamMemberUncheckedCreateWithoutUserInput[]
    connectOrCreate?: TeamMemberCreateOrConnectWithoutUserInput | TeamMemberCreateOrConnectWithoutUserInput[]
    upsert?: TeamMemberUpsertWithWhereUniqueWithoutUserInput | TeamMemberUpsertWithWhereUniqueWithoutUserInput[]
    createMany?: TeamMemberCreateManyUserInputEnvelope
    set?: TeamMemberWhereUniqueInput | TeamMemberWhereUniqueInput[]
    disconnect?: TeamMemberWhereUniqueInput | TeamMemberWhereUniqueInput[]
    delete?: TeamMemberWhereUniqueInput | TeamMemberWhereUniqueInput[]
    connect?: TeamMemberWhereUniqueInput | TeamMemberWhereUniqueInput[]
    update?: TeamMemberUpdateWithWhereUniqueWithoutUserInput | TeamMemberUpdateWithWhereUniqueWithoutUserInput[]
    updateMany?: TeamMemberUpdateManyWithWhereWithoutUserInput | TeamMemberUpdateManyWithWhereWithoutUserInput[]
    deleteMany?: TeamMemberScalarWhereInput | TeamMemberScalarWhereInput[]
  }

  export type UserCreateNestedOneWithoutUserPermissionsInput = {
    create?: XOR<UserCreateWithoutUserPermissionsInput, UserUncheckedCreateWithoutUserPermissionsInput>
    connectOrCreate?: UserCreateOrConnectWithoutUserPermissionsInput
    connect?: UserWhereUniqueInput
  }

  export type UserUpdateOneRequiredWithoutUserPermissionsNestedInput = {
    create?: XOR<UserCreateWithoutUserPermissionsInput, UserUncheckedCreateWithoutUserPermissionsInput>
    connectOrCreate?: UserCreateOrConnectWithoutUserPermissionsInput
    upsert?: UserUpsertWithoutUserPermissionsInput
    connect?: UserWhereUniqueInput
    update?: XOR<XOR<UserUpdateToOneWithWhereWithoutUserPermissionsInput, UserUpdateWithoutUserPermissionsInput>, UserUncheckedUpdateWithoutUserPermissionsInput>
  }

  export type UserCreateNestedOneWithoutUserRolesInput = {
    create?: XOR<UserCreateWithoutUserRolesInput, UserUncheckedCreateWithoutUserRolesInput>
    connectOrCreate?: UserCreateOrConnectWithoutUserRolesInput
    connect?: UserWhereUniqueInput
  }

  export type RoleCreateNestedOneWithoutUserRolesInput = {
    create?: XOR<RoleCreateWithoutUserRolesInput, RoleUncheckedCreateWithoutUserRolesInput>
    connectOrCreate?: RoleCreateOrConnectWithoutUserRolesInput
    connect?: RoleWhereUniqueInput
  }

  export type UserUpdateOneRequiredWithoutUserRolesNestedInput = {
    create?: XOR<UserCreateWithoutUserRolesInput, UserUncheckedCreateWithoutUserRolesInput>
    connectOrCreate?: UserCreateOrConnectWithoutUserRolesInput
    upsert?: UserUpsertWithoutUserRolesInput
    connect?: UserWhereUniqueInput
    update?: XOR<XOR<UserUpdateToOneWithWhereWithoutUserRolesInput, UserUpdateWithoutUserRolesInput>, UserUncheckedUpdateWithoutUserRolesInput>
  }

  export type RoleUpdateOneRequiredWithoutUserRolesNestedInput = {
    create?: XOR<RoleCreateWithoutUserRolesInput, RoleUncheckedCreateWithoutUserRolesInput>
    connectOrCreate?: RoleCreateOrConnectWithoutUserRolesInput
    upsert?: RoleUpsertWithoutUserRolesInput
    connect?: RoleWhereUniqueInput
    update?: XOR<XOR<RoleUpdateToOneWithWhereWithoutUserRolesInput, RoleUpdateWithoutUserRolesInput>, RoleUncheckedUpdateWithoutUserRolesInput>
  }

  export type TileCreateNestedManyWithoutTaskInput = {
    create?: XOR<TileCreateWithoutTaskInput, TileUncheckedCreateWithoutTaskInput> | TileCreateWithoutTaskInput[] | TileUncheckedCreateWithoutTaskInput[]
    connectOrCreate?: TileCreateOrConnectWithoutTaskInput | TileCreateOrConnectWithoutTaskInput[]
    createMany?: TileCreateManyTaskInputEnvelope
    connect?: TileWhereUniqueInput | TileWhereUniqueInput[]
  }

  export type TileUncheckedCreateNestedManyWithoutTaskInput = {
    create?: XOR<TileCreateWithoutTaskInput, TileUncheckedCreateWithoutTaskInput> | TileCreateWithoutTaskInput[] | TileUncheckedCreateWithoutTaskInput[]
    connectOrCreate?: TileCreateOrConnectWithoutTaskInput | TileCreateOrConnectWithoutTaskInput[]
    createMany?: TileCreateManyTaskInputEnvelope
    connect?: TileWhereUniqueInput | TileWhereUniqueInput[]
  }

  export type TileUpdateManyWithoutTaskNestedInput = {
    create?: XOR<TileCreateWithoutTaskInput, TileUncheckedCreateWithoutTaskInput> | TileCreateWithoutTaskInput[] | TileUncheckedCreateWithoutTaskInput[]
    connectOrCreate?: TileCreateOrConnectWithoutTaskInput | TileCreateOrConnectWithoutTaskInput[]
    upsert?: TileUpsertWithWhereUniqueWithoutTaskInput | TileUpsertWithWhereUniqueWithoutTaskInput[]
    createMany?: TileCreateManyTaskInputEnvelope
    set?: TileWhereUniqueInput | TileWhereUniqueInput[]
    disconnect?: TileWhereUniqueInput | TileWhereUniqueInput[]
    delete?: TileWhereUniqueInput | TileWhereUniqueInput[]
    connect?: TileWhereUniqueInput | TileWhereUniqueInput[]
    update?: TileUpdateWithWhereUniqueWithoutTaskInput | TileUpdateWithWhereUniqueWithoutTaskInput[]
    updateMany?: TileUpdateManyWithWhereWithoutTaskInput | TileUpdateManyWithWhereWithoutTaskInput[]
    deleteMany?: TileScalarWhereInput | TileScalarWhereInput[]
  }

  export type TileUncheckedUpdateManyWithoutTaskNestedInput = {
    create?: XOR<TileCreateWithoutTaskInput, TileUncheckedCreateWithoutTaskInput> | TileCreateWithoutTaskInput[] | TileUncheckedCreateWithoutTaskInput[]
    connectOrCreate?: TileCreateOrConnectWithoutTaskInput | TileCreateOrConnectWithoutTaskInput[]
    upsert?: TileUpsertWithWhereUniqueWithoutTaskInput | TileUpsertWithWhereUniqueWithoutTaskInput[]
    createMany?: TileCreateManyTaskInputEnvelope
    set?: TileWhereUniqueInput | TileWhereUniqueInput[]
    disconnect?: TileWhereUniqueInput | TileWhereUniqueInput[]
    delete?: TileWhereUniqueInput | TileWhereUniqueInput[]
    connect?: TileWhereUniqueInput | TileWhereUniqueInput[]
    update?: TileUpdateWithWhereUniqueWithoutTaskInput | TileUpdateWithWhereUniqueWithoutTaskInput[]
    updateMany?: TileUpdateManyWithWhereWithoutTaskInput | TileUpdateManyWithWhereWithoutTaskInput[]
    deleteMany?: TileScalarWhereInput | TileScalarWhereInput[]
  }

  export type BoardAuthorCreateNestedManyWithoutBoardInput = {
    create?: XOR<BoardAuthorCreateWithoutBoardInput, BoardAuthorUncheckedCreateWithoutBoardInput> | BoardAuthorCreateWithoutBoardInput[] | BoardAuthorUncheckedCreateWithoutBoardInput[]
    connectOrCreate?: BoardAuthorCreateOrConnectWithoutBoardInput | BoardAuthorCreateOrConnectWithoutBoardInput[]
    createMany?: BoardAuthorCreateManyBoardInputEnvelope
    connect?: BoardAuthorWhereUniqueInput | BoardAuthorWhereUniqueInput[]
  }

  export type TileCreateNestedManyWithoutBoardInput = {
    create?: XOR<TileCreateWithoutBoardInput, TileUncheckedCreateWithoutBoardInput> | TileCreateWithoutBoardInput[] | TileUncheckedCreateWithoutBoardInput[]
    connectOrCreate?: TileCreateOrConnectWithoutBoardInput | TileCreateOrConnectWithoutBoardInput[]
    createMany?: TileCreateManyBoardInputEnvelope
    connect?: TileWhereUniqueInput | TileWhereUniqueInput[]
  }

  export type PlayerBoardCreateNestedManyWithoutBoardInput = {
    create?: XOR<PlayerBoardCreateWithoutBoardInput, PlayerBoardUncheckedCreateWithoutBoardInput> | PlayerBoardCreateWithoutBoardInput[] | PlayerBoardUncheckedCreateWithoutBoardInput[]
    connectOrCreate?: PlayerBoardCreateOrConnectWithoutBoardInput | PlayerBoardCreateOrConnectWithoutBoardInput[]
    createMany?: PlayerBoardCreateManyBoardInputEnvelope
    connect?: PlayerBoardWhereUniqueInput | PlayerBoardWhereUniqueInput[]
  }

  export type BoardTeamCreateNestedManyWithoutBoardInput = {
    create?: XOR<BoardTeamCreateWithoutBoardInput, BoardTeamUncheckedCreateWithoutBoardInput> | BoardTeamCreateWithoutBoardInput[] | BoardTeamUncheckedCreateWithoutBoardInput[]
    connectOrCreate?: BoardTeamCreateOrConnectWithoutBoardInput | BoardTeamCreateOrConnectWithoutBoardInput[]
    createMany?: BoardTeamCreateManyBoardInputEnvelope
    connect?: BoardTeamWhereUniqueInput | BoardTeamWhereUniqueInput[]
  }

  export type BoardAuthorUncheckedCreateNestedManyWithoutBoardInput = {
    create?: XOR<BoardAuthorCreateWithoutBoardInput, BoardAuthorUncheckedCreateWithoutBoardInput> | BoardAuthorCreateWithoutBoardInput[] | BoardAuthorUncheckedCreateWithoutBoardInput[]
    connectOrCreate?: BoardAuthorCreateOrConnectWithoutBoardInput | BoardAuthorCreateOrConnectWithoutBoardInput[]
    createMany?: BoardAuthorCreateManyBoardInputEnvelope
    connect?: BoardAuthorWhereUniqueInput | BoardAuthorWhereUniqueInput[]
  }

  export type TileUncheckedCreateNestedManyWithoutBoardInput = {
    create?: XOR<TileCreateWithoutBoardInput, TileUncheckedCreateWithoutBoardInput> | TileCreateWithoutBoardInput[] | TileUncheckedCreateWithoutBoardInput[]
    connectOrCreate?: TileCreateOrConnectWithoutBoardInput | TileCreateOrConnectWithoutBoardInput[]
    createMany?: TileCreateManyBoardInputEnvelope
    connect?: TileWhereUniqueInput | TileWhereUniqueInput[]
  }

  export type PlayerBoardUncheckedCreateNestedManyWithoutBoardInput = {
    create?: XOR<PlayerBoardCreateWithoutBoardInput, PlayerBoardUncheckedCreateWithoutBoardInput> | PlayerBoardCreateWithoutBoardInput[] | PlayerBoardUncheckedCreateWithoutBoardInput[]
    connectOrCreate?: PlayerBoardCreateOrConnectWithoutBoardInput | PlayerBoardCreateOrConnectWithoutBoardInput[]
    createMany?: PlayerBoardCreateManyBoardInputEnvelope
    connect?: PlayerBoardWhereUniqueInput | PlayerBoardWhereUniqueInput[]
  }

  export type BoardTeamUncheckedCreateNestedManyWithoutBoardInput = {
    create?: XOR<BoardTeamCreateWithoutBoardInput, BoardTeamUncheckedCreateWithoutBoardInput> | BoardTeamCreateWithoutBoardInput[] | BoardTeamUncheckedCreateWithoutBoardInput[]
    connectOrCreate?: BoardTeamCreateOrConnectWithoutBoardInput | BoardTeamCreateOrConnectWithoutBoardInput[]
    createMany?: BoardTeamCreateManyBoardInputEnvelope
    connect?: BoardTeamWhereUniqueInput | BoardTeamWhereUniqueInput[]
  }

  export type NullableDateTimeFieldUpdateOperationsInput = {
    set?: Date | string | null
  }

  export type EnumBoardSizeFieldUpdateOperationsInput = {
    set?: $Enums.BoardSize
  }

  export type EnumBoardModeFieldUpdateOperationsInput = {
    set?: $Enums.BoardMode
  }

  export type NullableIntFieldUpdateOperationsInput = {
    set?: number | null
    increment?: number
    decrement?: number
    multiply?: number
    divide?: number
  }

  export type BoardAuthorUpdateManyWithoutBoardNestedInput = {
    create?: XOR<BoardAuthorCreateWithoutBoardInput, BoardAuthorUncheckedCreateWithoutBoardInput> | BoardAuthorCreateWithoutBoardInput[] | BoardAuthorUncheckedCreateWithoutBoardInput[]
    connectOrCreate?: BoardAuthorCreateOrConnectWithoutBoardInput | BoardAuthorCreateOrConnectWithoutBoardInput[]
    upsert?: BoardAuthorUpsertWithWhereUniqueWithoutBoardInput | BoardAuthorUpsertWithWhereUniqueWithoutBoardInput[]
    createMany?: BoardAuthorCreateManyBoardInputEnvelope
    set?: BoardAuthorWhereUniqueInput | BoardAuthorWhereUniqueInput[]
    disconnect?: BoardAuthorWhereUniqueInput | BoardAuthorWhereUniqueInput[]
    delete?: BoardAuthorWhereUniqueInput | BoardAuthorWhereUniqueInput[]
    connect?: BoardAuthorWhereUniqueInput | BoardAuthorWhereUniqueInput[]
    update?: BoardAuthorUpdateWithWhereUniqueWithoutBoardInput | BoardAuthorUpdateWithWhereUniqueWithoutBoardInput[]
    updateMany?: BoardAuthorUpdateManyWithWhereWithoutBoardInput | BoardAuthorUpdateManyWithWhereWithoutBoardInput[]
    deleteMany?: BoardAuthorScalarWhereInput | BoardAuthorScalarWhereInput[]
  }

  export type TileUpdateManyWithoutBoardNestedInput = {
    create?: XOR<TileCreateWithoutBoardInput, TileUncheckedCreateWithoutBoardInput> | TileCreateWithoutBoardInput[] | TileUncheckedCreateWithoutBoardInput[]
    connectOrCreate?: TileCreateOrConnectWithoutBoardInput | TileCreateOrConnectWithoutBoardInput[]
    upsert?: TileUpsertWithWhereUniqueWithoutBoardInput | TileUpsertWithWhereUniqueWithoutBoardInput[]
    createMany?: TileCreateManyBoardInputEnvelope
    set?: TileWhereUniqueInput | TileWhereUniqueInput[]
    disconnect?: TileWhereUniqueInput | TileWhereUniqueInput[]
    delete?: TileWhereUniqueInput | TileWhereUniqueInput[]
    connect?: TileWhereUniqueInput | TileWhereUniqueInput[]
    update?: TileUpdateWithWhereUniqueWithoutBoardInput | TileUpdateWithWhereUniqueWithoutBoardInput[]
    updateMany?: TileUpdateManyWithWhereWithoutBoardInput | TileUpdateManyWithWhereWithoutBoardInput[]
    deleteMany?: TileScalarWhereInput | TileScalarWhereInput[]
  }

  export type PlayerBoardUpdateManyWithoutBoardNestedInput = {
    create?: XOR<PlayerBoardCreateWithoutBoardInput, PlayerBoardUncheckedCreateWithoutBoardInput> | PlayerBoardCreateWithoutBoardInput[] | PlayerBoardUncheckedCreateWithoutBoardInput[]
    connectOrCreate?: PlayerBoardCreateOrConnectWithoutBoardInput | PlayerBoardCreateOrConnectWithoutBoardInput[]
    upsert?: PlayerBoardUpsertWithWhereUniqueWithoutBoardInput | PlayerBoardUpsertWithWhereUniqueWithoutBoardInput[]
    createMany?: PlayerBoardCreateManyBoardInputEnvelope
    set?: PlayerBoardWhereUniqueInput | PlayerBoardWhereUniqueInput[]
    disconnect?: PlayerBoardWhereUniqueInput | PlayerBoardWhereUniqueInput[]
    delete?: PlayerBoardWhereUniqueInput | PlayerBoardWhereUniqueInput[]
    connect?: PlayerBoardWhereUniqueInput | PlayerBoardWhereUniqueInput[]
    update?: PlayerBoardUpdateWithWhereUniqueWithoutBoardInput | PlayerBoardUpdateWithWhereUniqueWithoutBoardInput[]
    updateMany?: PlayerBoardUpdateManyWithWhereWithoutBoardInput | PlayerBoardUpdateManyWithWhereWithoutBoardInput[]
    deleteMany?: PlayerBoardScalarWhereInput | PlayerBoardScalarWhereInput[]
  }

  export type BoardTeamUpdateManyWithoutBoardNestedInput = {
    create?: XOR<BoardTeamCreateWithoutBoardInput, BoardTeamUncheckedCreateWithoutBoardInput> | BoardTeamCreateWithoutBoardInput[] | BoardTeamUncheckedCreateWithoutBoardInput[]
    connectOrCreate?: BoardTeamCreateOrConnectWithoutBoardInput | BoardTeamCreateOrConnectWithoutBoardInput[]
    upsert?: BoardTeamUpsertWithWhereUniqueWithoutBoardInput | BoardTeamUpsertWithWhereUniqueWithoutBoardInput[]
    createMany?: BoardTeamCreateManyBoardInputEnvelope
    set?: BoardTeamWhereUniqueInput | BoardTeamWhereUniqueInput[]
    disconnect?: BoardTeamWhereUniqueInput | BoardTeamWhereUniqueInput[]
    delete?: BoardTeamWhereUniqueInput | BoardTeamWhereUniqueInput[]
    connect?: BoardTeamWhereUniqueInput | BoardTeamWhereUniqueInput[]
    update?: BoardTeamUpdateWithWhereUniqueWithoutBoardInput | BoardTeamUpdateWithWhereUniqueWithoutBoardInput[]
    updateMany?: BoardTeamUpdateManyWithWhereWithoutBoardInput | BoardTeamUpdateManyWithWhereWithoutBoardInput[]
    deleteMany?: BoardTeamScalarWhereInput | BoardTeamScalarWhereInput[]
  }

  export type BoardAuthorUncheckedUpdateManyWithoutBoardNestedInput = {
    create?: XOR<BoardAuthorCreateWithoutBoardInput, BoardAuthorUncheckedCreateWithoutBoardInput> | BoardAuthorCreateWithoutBoardInput[] | BoardAuthorUncheckedCreateWithoutBoardInput[]
    connectOrCreate?: BoardAuthorCreateOrConnectWithoutBoardInput | BoardAuthorCreateOrConnectWithoutBoardInput[]
    upsert?: BoardAuthorUpsertWithWhereUniqueWithoutBoardInput | BoardAuthorUpsertWithWhereUniqueWithoutBoardInput[]
    createMany?: BoardAuthorCreateManyBoardInputEnvelope
    set?: BoardAuthorWhereUniqueInput | BoardAuthorWhereUniqueInput[]
    disconnect?: BoardAuthorWhereUniqueInput | BoardAuthorWhereUniqueInput[]
    delete?: BoardAuthorWhereUniqueInput | BoardAuthorWhereUniqueInput[]
    connect?: BoardAuthorWhereUniqueInput | BoardAuthorWhereUniqueInput[]
    update?: BoardAuthorUpdateWithWhereUniqueWithoutBoardInput | BoardAuthorUpdateWithWhereUniqueWithoutBoardInput[]
    updateMany?: BoardAuthorUpdateManyWithWhereWithoutBoardInput | BoardAuthorUpdateManyWithWhereWithoutBoardInput[]
    deleteMany?: BoardAuthorScalarWhereInput | BoardAuthorScalarWhereInput[]
  }

  export type TileUncheckedUpdateManyWithoutBoardNestedInput = {
    create?: XOR<TileCreateWithoutBoardInput, TileUncheckedCreateWithoutBoardInput> | TileCreateWithoutBoardInput[] | TileUncheckedCreateWithoutBoardInput[]
    connectOrCreate?: TileCreateOrConnectWithoutBoardInput | TileCreateOrConnectWithoutBoardInput[]
    upsert?: TileUpsertWithWhereUniqueWithoutBoardInput | TileUpsertWithWhereUniqueWithoutBoardInput[]
    createMany?: TileCreateManyBoardInputEnvelope
    set?: TileWhereUniqueInput | TileWhereUniqueInput[]
    disconnect?: TileWhereUniqueInput | TileWhereUniqueInput[]
    delete?: TileWhereUniqueInput | TileWhereUniqueInput[]
    connect?: TileWhereUniqueInput | TileWhereUniqueInput[]
    update?: TileUpdateWithWhereUniqueWithoutBoardInput | TileUpdateWithWhereUniqueWithoutBoardInput[]
    updateMany?: TileUpdateManyWithWhereWithoutBoardInput | TileUpdateManyWithWhereWithoutBoardInput[]
    deleteMany?: TileScalarWhereInput | TileScalarWhereInput[]
  }

  export type PlayerBoardUncheckedUpdateManyWithoutBoardNestedInput = {
    create?: XOR<PlayerBoardCreateWithoutBoardInput, PlayerBoardUncheckedCreateWithoutBoardInput> | PlayerBoardCreateWithoutBoardInput[] | PlayerBoardUncheckedCreateWithoutBoardInput[]
    connectOrCreate?: PlayerBoardCreateOrConnectWithoutBoardInput | PlayerBoardCreateOrConnectWithoutBoardInput[]
    upsert?: PlayerBoardUpsertWithWhereUniqueWithoutBoardInput | PlayerBoardUpsertWithWhereUniqueWithoutBoardInput[]
    createMany?: PlayerBoardCreateManyBoardInputEnvelope
    set?: PlayerBoardWhereUniqueInput | PlayerBoardWhereUniqueInput[]
    disconnect?: PlayerBoardWhereUniqueInput | PlayerBoardWhereUniqueInput[]
    delete?: PlayerBoardWhereUniqueInput | PlayerBoardWhereUniqueInput[]
    connect?: PlayerBoardWhereUniqueInput | PlayerBoardWhereUniqueInput[]
    update?: PlayerBoardUpdateWithWhereUniqueWithoutBoardInput | PlayerBoardUpdateWithWhereUniqueWithoutBoardInput[]
    updateMany?: PlayerBoardUpdateManyWithWhereWithoutBoardInput | PlayerBoardUpdateManyWithWhereWithoutBoardInput[]
    deleteMany?: PlayerBoardScalarWhereInput | PlayerBoardScalarWhereInput[]
  }

  export type BoardTeamUncheckedUpdateManyWithoutBoardNestedInput = {
    create?: XOR<BoardTeamCreateWithoutBoardInput, BoardTeamUncheckedCreateWithoutBoardInput> | BoardTeamCreateWithoutBoardInput[] | BoardTeamUncheckedCreateWithoutBoardInput[]
    connectOrCreate?: BoardTeamCreateOrConnectWithoutBoardInput | BoardTeamCreateOrConnectWithoutBoardInput[]
    upsert?: BoardTeamUpsertWithWhereUniqueWithoutBoardInput | BoardTeamUpsertWithWhereUniqueWithoutBoardInput[]
    createMany?: BoardTeamCreateManyBoardInputEnvelope
    set?: BoardTeamWhereUniqueInput | BoardTeamWhereUniqueInput[]
    disconnect?: BoardTeamWhereUniqueInput | BoardTeamWhereUniqueInput[]
    delete?: BoardTeamWhereUniqueInput | BoardTeamWhereUniqueInput[]
    connect?: BoardTeamWhereUniqueInput | BoardTeamWhereUniqueInput[]
    update?: BoardTeamUpdateWithWhereUniqueWithoutBoardInput | BoardTeamUpdateWithWhereUniqueWithoutBoardInput[]
    updateMany?: BoardTeamUpdateManyWithWhereWithoutBoardInput | BoardTeamUpdateManyWithWhereWithoutBoardInput[]
    deleteMany?: BoardTeamScalarWhereInput | BoardTeamScalarWhereInput[]
  }

  export type BoardCreateNestedOneWithoutBoardTeamsInput = {
    create?: XOR<BoardCreateWithoutBoardTeamsInput, BoardUncheckedCreateWithoutBoardTeamsInput>
    connectOrCreate?: BoardCreateOrConnectWithoutBoardTeamsInput
    connect?: BoardWhereUniqueInput
  }

  export type TeamCreateNestedOneWithoutBoardTeamsInput = {
    create?: XOR<TeamCreateWithoutBoardTeamsInput, TeamUncheckedCreateWithoutBoardTeamsInput>
    connectOrCreate?: TeamCreateOrConnectWithoutBoardTeamsInput
    connect?: TeamWhereUniqueInput
  }

  export type BoardUpdateOneRequiredWithoutBoardTeamsNestedInput = {
    create?: XOR<BoardCreateWithoutBoardTeamsInput, BoardUncheckedCreateWithoutBoardTeamsInput>
    connectOrCreate?: BoardCreateOrConnectWithoutBoardTeamsInput
    upsert?: BoardUpsertWithoutBoardTeamsInput
    connect?: BoardWhereUniqueInput
    update?: XOR<XOR<BoardUpdateToOneWithWhereWithoutBoardTeamsInput, BoardUpdateWithoutBoardTeamsInput>, BoardUncheckedUpdateWithoutBoardTeamsInput>
  }

  export type TeamUpdateOneRequiredWithoutBoardTeamsNestedInput = {
    create?: XOR<TeamCreateWithoutBoardTeamsInput, TeamUncheckedCreateWithoutBoardTeamsInput>
    connectOrCreate?: TeamCreateOrConnectWithoutBoardTeamsInput
    upsert?: TeamUpsertWithoutBoardTeamsInput
    connect?: TeamWhereUniqueInput
    update?: XOR<XOR<TeamUpdateToOneWithWhereWithoutBoardTeamsInput, TeamUpdateWithoutBoardTeamsInput>, TeamUncheckedUpdateWithoutBoardTeamsInput>
  }

  export type BoardCreateNestedOneWithoutAuthorsInput = {
    create?: XOR<BoardCreateWithoutAuthorsInput, BoardUncheckedCreateWithoutAuthorsInput>
    connectOrCreate?: BoardCreateOrConnectWithoutAuthorsInput
    connect?: BoardWhereUniqueInput
  }

  export type UserCreateNestedOneWithoutBoardAuthorsInput = {
    create?: XOR<UserCreateWithoutBoardAuthorsInput, UserUncheckedCreateWithoutBoardAuthorsInput>
    connectOrCreate?: UserCreateOrConnectWithoutBoardAuthorsInput
    connect?: UserWhereUniqueInput
  }

  export type BoolFieldUpdateOperationsInput = {
    set?: boolean
  }

  export type BoardUpdateOneRequiredWithoutAuthorsNestedInput = {
    create?: XOR<BoardCreateWithoutAuthorsInput, BoardUncheckedCreateWithoutAuthorsInput>
    connectOrCreate?: BoardCreateOrConnectWithoutAuthorsInput
    upsert?: BoardUpsertWithoutAuthorsInput
    connect?: BoardWhereUniqueInput
    update?: XOR<XOR<BoardUpdateToOneWithWhereWithoutAuthorsInput, BoardUpdateWithoutAuthorsInput>, BoardUncheckedUpdateWithoutAuthorsInput>
  }

  export type UserUpdateOneRequiredWithoutBoardAuthorsNestedInput = {
    create?: XOR<UserCreateWithoutBoardAuthorsInput, UserUncheckedCreateWithoutBoardAuthorsInput>
    connectOrCreate?: UserCreateOrConnectWithoutBoardAuthorsInput
    upsert?: UserUpsertWithoutBoardAuthorsInput
    connect?: UserWhereUniqueInput
    update?: XOR<XOR<UserUpdateToOneWithWhereWithoutBoardAuthorsInput, UserUpdateWithoutBoardAuthorsInput>, UserUncheckedUpdateWithoutBoardAuthorsInput>
  }

  export type BoardCreateNestedOneWithoutTilesInput = {
    create?: XOR<BoardCreateWithoutTilesInput, BoardUncheckedCreateWithoutTilesInput>
    connectOrCreate?: BoardCreateOrConnectWithoutTilesInput
    connect?: BoardWhereUniqueInput
  }

  export type TaskCreateNestedOneWithoutTilesInput = {
    create?: XOR<TaskCreateWithoutTilesInput, TaskUncheckedCreateWithoutTilesInput>
    connectOrCreate?: TaskCreateOrConnectWithoutTilesInput
    connect?: TaskWhereUniqueInput
  }

  export type CompletedTileCreateNestedManyWithoutTileInput = {
    create?: XOR<CompletedTileCreateWithoutTileInput, CompletedTileUncheckedCreateWithoutTileInput> | CompletedTileCreateWithoutTileInput[] | CompletedTileUncheckedCreateWithoutTileInput[]
    connectOrCreate?: CompletedTileCreateOrConnectWithoutTileInput | CompletedTileCreateOrConnectWithoutTileInput[]
    createMany?: CompletedTileCreateManyTileInputEnvelope
    connect?: CompletedTileWhereUniqueInput | CompletedTileWhereUniqueInput[]
  }

  export type CompletedTileUncheckedCreateNestedManyWithoutTileInput = {
    create?: XOR<CompletedTileCreateWithoutTileInput, CompletedTileUncheckedCreateWithoutTileInput> | CompletedTileCreateWithoutTileInput[] | CompletedTileUncheckedCreateWithoutTileInput[]
    connectOrCreate?: CompletedTileCreateOrConnectWithoutTileInput | CompletedTileCreateOrConnectWithoutTileInput[]
    createMany?: CompletedTileCreateManyTileInputEnvelope
    connect?: CompletedTileWhereUniqueInput | CompletedTileWhereUniqueInput[]
  }

  export type IntFieldUpdateOperationsInput = {
    set?: number
    increment?: number
    decrement?: number
    multiply?: number
    divide?: number
  }

  export type EnumTileTypeFieldUpdateOperationsInput = {
    set?: $Enums.TileType
  }

  export type BoardUpdateOneRequiredWithoutTilesNestedInput = {
    create?: XOR<BoardCreateWithoutTilesInput, BoardUncheckedCreateWithoutTilesInput>
    connectOrCreate?: BoardCreateOrConnectWithoutTilesInput
    upsert?: BoardUpsertWithoutTilesInput
    connect?: BoardWhereUniqueInput
    update?: XOR<XOR<BoardUpdateToOneWithWhereWithoutTilesInput, BoardUpdateWithoutTilesInput>, BoardUncheckedUpdateWithoutTilesInput>
  }

  export type TaskUpdateOneWithoutTilesNestedInput = {
    create?: XOR<TaskCreateWithoutTilesInput, TaskUncheckedCreateWithoutTilesInput>
    connectOrCreate?: TaskCreateOrConnectWithoutTilesInput
    upsert?: TaskUpsertWithoutTilesInput
    disconnect?: TaskWhereInput | boolean
    delete?: TaskWhereInput | boolean
    connect?: TaskWhereUniqueInput
    update?: XOR<XOR<TaskUpdateToOneWithWhereWithoutTilesInput, TaskUpdateWithoutTilesInput>, TaskUncheckedUpdateWithoutTilesInput>
  }

  export type CompletedTileUpdateManyWithoutTileNestedInput = {
    create?: XOR<CompletedTileCreateWithoutTileInput, CompletedTileUncheckedCreateWithoutTileInput> | CompletedTileCreateWithoutTileInput[] | CompletedTileUncheckedCreateWithoutTileInput[]
    connectOrCreate?: CompletedTileCreateOrConnectWithoutTileInput | CompletedTileCreateOrConnectWithoutTileInput[]
    upsert?: CompletedTileUpsertWithWhereUniqueWithoutTileInput | CompletedTileUpsertWithWhereUniqueWithoutTileInput[]
    createMany?: CompletedTileCreateManyTileInputEnvelope
    set?: CompletedTileWhereUniqueInput | CompletedTileWhereUniqueInput[]
    disconnect?: CompletedTileWhereUniqueInput | CompletedTileWhereUniqueInput[]
    delete?: CompletedTileWhereUniqueInput | CompletedTileWhereUniqueInput[]
    connect?: CompletedTileWhereUniqueInput | CompletedTileWhereUniqueInput[]
    update?: CompletedTileUpdateWithWhereUniqueWithoutTileInput | CompletedTileUpdateWithWhereUniqueWithoutTileInput[]
    updateMany?: CompletedTileUpdateManyWithWhereWithoutTileInput | CompletedTileUpdateManyWithWhereWithoutTileInput[]
    deleteMany?: CompletedTileScalarWhereInput | CompletedTileScalarWhereInput[]
  }

  export type CompletedTileUncheckedUpdateManyWithoutTileNestedInput = {
    create?: XOR<CompletedTileCreateWithoutTileInput, CompletedTileUncheckedCreateWithoutTileInput> | CompletedTileCreateWithoutTileInput[] | CompletedTileUncheckedCreateWithoutTileInput[]
    connectOrCreate?: CompletedTileCreateOrConnectWithoutTileInput | CompletedTileCreateOrConnectWithoutTileInput[]
    upsert?: CompletedTileUpsertWithWhereUniqueWithoutTileInput | CompletedTileUpsertWithWhereUniqueWithoutTileInput[]
    createMany?: CompletedTileCreateManyTileInputEnvelope
    set?: CompletedTileWhereUniqueInput | CompletedTileWhereUniqueInput[]
    disconnect?: CompletedTileWhereUniqueInput | CompletedTileWhereUniqueInput[]
    delete?: CompletedTileWhereUniqueInput | CompletedTileWhereUniqueInput[]
    connect?: CompletedTileWhereUniqueInput | CompletedTileWhereUniqueInput[]
    update?: CompletedTileUpdateWithWhereUniqueWithoutTileInput | CompletedTileUpdateWithWhereUniqueWithoutTileInput[]
    updateMany?: CompletedTileUpdateManyWithWhereWithoutTileInput | CompletedTileUpdateManyWithWhereWithoutTileInput[]
    deleteMany?: CompletedTileScalarWhereInput | CompletedTileScalarWhereInput[]
  }

  export type UserCreateNestedOneWithoutPlayerBoardsInput = {
    create?: XOR<UserCreateWithoutPlayerBoardsInput, UserUncheckedCreateWithoutPlayerBoardsInput>
    connectOrCreate?: UserCreateOrConnectWithoutPlayerBoardsInput
    connect?: UserWhereUniqueInput
  }

  export type BoardCreateNestedOneWithoutPlayerBoardsInput = {
    create?: XOR<BoardCreateWithoutPlayerBoardsInput, BoardUncheckedCreateWithoutPlayerBoardsInput>
    connectOrCreate?: BoardCreateOrConnectWithoutPlayerBoardsInput
    connect?: BoardWhereUniqueInput
  }

  export type TeamCreateNestedOneWithoutPlayerBoardsInput = {
    create?: XOR<TeamCreateWithoutPlayerBoardsInput, TeamUncheckedCreateWithoutPlayerBoardsInput>
    connectOrCreate?: TeamCreateOrConnectWithoutPlayerBoardsInput
    connect?: TeamWhereUniqueInput
  }

  export type CompletedTileCreateNestedManyWithoutPlayerBoardInput = {
    create?: XOR<CompletedTileCreateWithoutPlayerBoardInput, CompletedTileUncheckedCreateWithoutPlayerBoardInput> | CompletedTileCreateWithoutPlayerBoardInput[] | CompletedTileUncheckedCreateWithoutPlayerBoardInput[]
    connectOrCreate?: CompletedTileCreateOrConnectWithoutPlayerBoardInput | CompletedTileCreateOrConnectWithoutPlayerBoardInput[]
    createMany?: CompletedTileCreateManyPlayerBoardInputEnvelope
    connect?: CompletedTileWhereUniqueInput | CompletedTileWhereUniqueInput[]
  }

  export type CompletedTileUncheckedCreateNestedManyWithoutPlayerBoardInput = {
    create?: XOR<CompletedTileCreateWithoutPlayerBoardInput, CompletedTileUncheckedCreateWithoutPlayerBoardInput> | CompletedTileCreateWithoutPlayerBoardInput[] | CompletedTileUncheckedCreateWithoutPlayerBoardInput[]
    connectOrCreate?: CompletedTileCreateOrConnectWithoutPlayerBoardInput | CompletedTileCreateOrConnectWithoutPlayerBoardInput[]
    createMany?: CompletedTileCreateManyPlayerBoardInputEnvelope
    connect?: CompletedTileWhereUniqueInput | CompletedTileWhereUniqueInput[]
  }

  export type UserUpdateOneRequiredWithoutPlayerBoardsNestedInput = {
    create?: XOR<UserCreateWithoutPlayerBoardsInput, UserUncheckedCreateWithoutPlayerBoardsInput>
    connectOrCreate?: UserCreateOrConnectWithoutPlayerBoardsInput
    upsert?: UserUpsertWithoutPlayerBoardsInput
    connect?: UserWhereUniqueInput
    update?: XOR<XOR<UserUpdateToOneWithWhereWithoutPlayerBoardsInput, UserUpdateWithoutPlayerBoardsInput>, UserUncheckedUpdateWithoutPlayerBoardsInput>
  }

  export type BoardUpdateOneRequiredWithoutPlayerBoardsNestedInput = {
    create?: XOR<BoardCreateWithoutPlayerBoardsInput, BoardUncheckedCreateWithoutPlayerBoardsInput>
    connectOrCreate?: BoardCreateOrConnectWithoutPlayerBoardsInput
    upsert?: BoardUpsertWithoutPlayerBoardsInput
    connect?: BoardWhereUniqueInput
    update?: XOR<XOR<BoardUpdateToOneWithWhereWithoutPlayerBoardsInput, BoardUpdateWithoutPlayerBoardsInput>, BoardUncheckedUpdateWithoutPlayerBoardsInput>
  }

  export type TeamUpdateOneWithoutPlayerBoardsNestedInput = {
    create?: XOR<TeamCreateWithoutPlayerBoardsInput, TeamUncheckedCreateWithoutPlayerBoardsInput>
    connectOrCreate?: TeamCreateOrConnectWithoutPlayerBoardsInput
    upsert?: TeamUpsertWithoutPlayerBoardsInput
    disconnect?: TeamWhereInput | boolean
    delete?: TeamWhereInput | boolean
    connect?: TeamWhereUniqueInput
    update?: XOR<XOR<TeamUpdateToOneWithWhereWithoutPlayerBoardsInput, TeamUpdateWithoutPlayerBoardsInput>, TeamUncheckedUpdateWithoutPlayerBoardsInput>
  }

  export type CompletedTileUpdateManyWithoutPlayerBoardNestedInput = {
    create?: XOR<CompletedTileCreateWithoutPlayerBoardInput, CompletedTileUncheckedCreateWithoutPlayerBoardInput> | CompletedTileCreateWithoutPlayerBoardInput[] | CompletedTileUncheckedCreateWithoutPlayerBoardInput[]
    connectOrCreate?: CompletedTileCreateOrConnectWithoutPlayerBoardInput | CompletedTileCreateOrConnectWithoutPlayerBoardInput[]
    upsert?: CompletedTileUpsertWithWhereUniqueWithoutPlayerBoardInput | CompletedTileUpsertWithWhereUniqueWithoutPlayerBoardInput[]
    createMany?: CompletedTileCreateManyPlayerBoardInputEnvelope
    set?: CompletedTileWhereUniqueInput | CompletedTileWhereUniqueInput[]
    disconnect?: CompletedTileWhereUniqueInput | CompletedTileWhereUniqueInput[]
    delete?: CompletedTileWhereUniqueInput | CompletedTileWhereUniqueInput[]
    connect?: CompletedTileWhereUniqueInput | CompletedTileWhereUniqueInput[]
    update?: CompletedTileUpdateWithWhereUniqueWithoutPlayerBoardInput | CompletedTileUpdateWithWhereUniqueWithoutPlayerBoardInput[]
    updateMany?: CompletedTileUpdateManyWithWhereWithoutPlayerBoardInput | CompletedTileUpdateManyWithWhereWithoutPlayerBoardInput[]
    deleteMany?: CompletedTileScalarWhereInput | CompletedTileScalarWhereInput[]
  }

  export type CompletedTileUncheckedUpdateManyWithoutPlayerBoardNestedInput = {
    create?: XOR<CompletedTileCreateWithoutPlayerBoardInput, CompletedTileUncheckedCreateWithoutPlayerBoardInput> | CompletedTileCreateWithoutPlayerBoardInput[] | CompletedTileUncheckedCreateWithoutPlayerBoardInput[]
    connectOrCreate?: CompletedTileCreateOrConnectWithoutPlayerBoardInput | CompletedTileCreateOrConnectWithoutPlayerBoardInput[]
    upsert?: CompletedTileUpsertWithWhereUniqueWithoutPlayerBoardInput | CompletedTileUpsertWithWhereUniqueWithoutPlayerBoardInput[]
    createMany?: CompletedTileCreateManyPlayerBoardInputEnvelope
    set?: CompletedTileWhereUniqueInput | CompletedTileWhereUniqueInput[]
    disconnect?: CompletedTileWhereUniqueInput | CompletedTileWhereUniqueInput[]
    delete?: CompletedTileWhereUniqueInput | CompletedTileWhereUniqueInput[]
    connect?: CompletedTileWhereUniqueInput | CompletedTileWhereUniqueInput[]
    update?: CompletedTileUpdateWithWhereUniqueWithoutPlayerBoardInput | CompletedTileUpdateWithWhereUniqueWithoutPlayerBoardInput[]
    updateMany?: CompletedTileUpdateManyWithWhereWithoutPlayerBoardInput | CompletedTileUpdateManyWithWhereWithoutPlayerBoardInput[]
    deleteMany?: CompletedTileScalarWhereInput | CompletedTileScalarWhereInput[]
  }

  export type PlayerBoardCreateNestedOneWithoutCompletedTilesInput = {
    create?: XOR<PlayerBoardCreateWithoutCompletedTilesInput, PlayerBoardUncheckedCreateWithoutCompletedTilesInput>
    connectOrCreate?: PlayerBoardCreateOrConnectWithoutCompletedTilesInput
    connect?: PlayerBoardWhereUniqueInput
  }

  export type TileCreateNestedOneWithoutCompletedTilesInput = {
    create?: XOR<TileCreateWithoutCompletedTilesInput, TileUncheckedCreateWithoutCompletedTilesInput>
    connectOrCreate?: TileCreateOrConnectWithoutCompletedTilesInput
    connect?: TileWhereUniqueInput
  }

  export type EnumCompletionSourceFieldUpdateOperationsInput = {
    set?: $Enums.CompletionSource
  }

  export type PlayerBoardUpdateOneRequiredWithoutCompletedTilesNestedInput = {
    create?: XOR<PlayerBoardCreateWithoutCompletedTilesInput, PlayerBoardUncheckedCreateWithoutCompletedTilesInput>
    connectOrCreate?: PlayerBoardCreateOrConnectWithoutCompletedTilesInput
    upsert?: PlayerBoardUpsertWithoutCompletedTilesInput
    connect?: PlayerBoardWhereUniqueInput
    update?: XOR<XOR<PlayerBoardUpdateToOneWithWhereWithoutCompletedTilesInput, PlayerBoardUpdateWithoutCompletedTilesInput>, PlayerBoardUncheckedUpdateWithoutCompletedTilesInput>
  }

  export type TileUpdateOneRequiredWithoutCompletedTilesNestedInput = {
    create?: XOR<TileCreateWithoutCompletedTilesInput, TileUncheckedCreateWithoutCompletedTilesInput>
    connectOrCreate?: TileCreateOrConnectWithoutCompletedTilesInput
    upsert?: TileUpsertWithoutCompletedTilesInput
    connect?: TileWhereUniqueInput
    update?: XOR<XOR<TileUpdateToOneWithWhereWithoutCompletedTilesInput, TileUpdateWithoutCompletedTilesInput>, TileUncheckedUpdateWithoutCompletedTilesInput>
  }

  export type TeamMemberCreateNestedManyWithoutTeamInput = {
    create?: XOR<TeamMemberCreateWithoutTeamInput, TeamMemberUncheckedCreateWithoutTeamInput> | TeamMemberCreateWithoutTeamInput[] | TeamMemberUncheckedCreateWithoutTeamInput[]
    connectOrCreate?: TeamMemberCreateOrConnectWithoutTeamInput | TeamMemberCreateOrConnectWithoutTeamInput[]
    createMany?: TeamMemberCreateManyTeamInputEnvelope
    connect?: TeamMemberWhereUniqueInput | TeamMemberWhereUniqueInput[]
  }

  export type BoardTeamCreateNestedManyWithoutTeamInput = {
    create?: XOR<BoardTeamCreateWithoutTeamInput, BoardTeamUncheckedCreateWithoutTeamInput> | BoardTeamCreateWithoutTeamInput[] | BoardTeamUncheckedCreateWithoutTeamInput[]
    connectOrCreate?: BoardTeamCreateOrConnectWithoutTeamInput | BoardTeamCreateOrConnectWithoutTeamInput[]
    createMany?: BoardTeamCreateManyTeamInputEnvelope
    connect?: BoardTeamWhereUniqueInput | BoardTeamWhereUniqueInput[]
  }

  export type PlayerBoardCreateNestedManyWithoutTeamInput = {
    create?: XOR<PlayerBoardCreateWithoutTeamInput, PlayerBoardUncheckedCreateWithoutTeamInput> | PlayerBoardCreateWithoutTeamInput[] | PlayerBoardUncheckedCreateWithoutTeamInput[]
    connectOrCreate?: PlayerBoardCreateOrConnectWithoutTeamInput | PlayerBoardCreateOrConnectWithoutTeamInput[]
    createMany?: PlayerBoardCreateManyTeamInputEnvelope
    connect?: PlayerBoardWhereUniqueInput | PlayerBoardWhereUniqueInput[]
  }

  export type TeamMemberUncheckedCreateNestedManyWithoutTeamInput = {
    create?: XOR<TeamMemberCreateWithoutTeamInput, TeamMemberUncheckedCreateWithoutTeamInput> | TeamMemberCreateWithoutTeamInput[] | TeamMemberUncheckedCreateWithoutTeamInput[]
    connectOrCreate?: TeamMemberCreateOrConnectWithoutTeamInput | TeamMemberCreateOrConnectWithoutTeamInput[]
    createMany?: TeamMemberCreateManyTeamInputEnvelope
    connect?: TeamMemberWhereUniqueInput | TeamMemberWhereUniqueInput[]
  }

  export type BoardTeamUncheckedCreateNestedManyWithoutTeamInput = {
    create?: XOR<BoardTeamCreateWithoutTeamInput, BoardTeamUncheckedCreateWithoutTeamInput> | BoardTeamCreateWithoutTeamInput[] | BoardTeamUncheckedCreateWithoutTeamInput[]
    connectOrCreate?: BoardTeamCreateOrConnectWithoutTeamInput | BoardTeamCreateOrConnectWithoutTeamInput[]
    createMany?: BoardTeamCreateManyTeamInputEnvelope
    connect?: BoardTeamWhereUniqueInput | BoardTeamWhereUniqueInput[]
  }

  export type PlayerBoardUncheckedCreateNestedManyWithoutTeamInput = {
    create?: XOR<PlayerBoardCreateWithoutTeamInput, PlayerBoardUncheckedCreateWithoutTeamInput> | PlayerBoardCreateWithoutTeamInput[] | PlayerBoardUncheckedCreateWithoutTeamInput[]
    connectOrCreate?: PlayerBoardCreateOrConnectWithoutTeamInput | PlayerBoardCreateOrConnectWithoutTeamInput[]
    createMany?: PlayerBoardCreateManyTeamInputEnvelope
    connect?: PlayerBoardWhereUniqueInput | PlayerBoardWhereUniqueInput[]
  }

  export type TeamMemberUpdateManyWithoutTeamNestedInput = {
    create?: XOR<TeamMemberCreateWithoutTeamInput, TeamMemberUncheckedCreateWithoutTeamInput> | TeamMemberCreateWithoutTeamInput[] | TeamMemberUncheckedCreateWithoutTeamInput[]
    connectOrCreate?: TeamMemberCreateOrConnectWithoutTeamInput | TeamMemberCreateOrConnectWithoutTeamInput[]
    upsert?: TeamMemberUpsertWithWhereUniqueWithoutTeamInput | TeamMemberUpsertWithWhereUniqueWithoutTeamInput[]
    createMany?: TeamMemberCreateManyTeamInputEnvelope
    set?: TeamMemberWhereUniqueInput | TeamMemberWhereUniqueInput[]
    disconnect?: TeamMemberWhereUniqueInput | TeamMemberWhereUniqueInput[]
    delete?: TeamMemberWhereUniqueInput | TeamMemberWhereUniqueInput[]
    connect?: TeamMemberWhereUniqueInput | TeamMemberWhereUniqueInput[]
    update?: TeamMemberUpdateWithWhereUniqueWithoutTeamInput | TeamMemberUpdateWithWhereUniqueWithoutTeamInput[]
    updateMany?: TeamMemberUpdateManyWithWhereWithoutTeamInput | TeamMemberUpdateManyWithWhereWithoutTeamInput[]
    deleteMany?: TeamMemberScalarWhereInput | TeamMemberScalarWhereInput[]
  }

  export type BoardTeamUpdateManyWithoutTeamNestedInput = {
    create?: XOR<BoardTeamCreateWithoutTeamInput, BoardTeamUncheckedCreateWithoutTeamInput> | BoardTeamCreateWithoutTeamInput[] | BoardTeamUncheckedCreateWithoutTeamInput[]
    connectOrCreate?: BoardTeamCreateOrConnectWithoutTeamInput | BoardTeamCreateOrConnectWithoutTeamInput[]
    upsert?: BoardTeamUpsertWithWhereUniqueWithoutTeamInput | BoardTeamUpsertWithWhereUniqueWithoutTeamInput[]
    createMany?: BoardTeamCreateManyTeamInputEnvelope
    set?: BoardTeamWhereUniqueInput | BoardTeamWhereUniqueInput[]
    disconnect?: BoardTeamWhereUniqueInput | BoardTeamWhereUniqueInput[]
    delete?: BoardTeamWhereUniqueInput | BoardTeamWhereUniqueInput[]
    connect?: BoardTeamWhereUniqueInput | BoardTeamWhereUniqueInput[]
    update?: BoardTeamUpdateWithWhereUniqueWithoutTeamInput | BoardTeamUpdateWithWhereUniqueWithoutTeamInput[]
    updateMany?: BoardTeamUpdateManyWithWhereWithoutTeamInput | BoardTeamUpdateManyWithWhereWithoutTeamInput[]
    deleteMany?: BoardTeamScalarWhereInput | BoardTeamScalarWhereInput[]
  }

  export type PlayerBoardUpdateManyWithoutTeamNestedInput = {
    create?: XOR<PlayerBoardCreateWithoutTeamInput, PlayerBoardUncheckedCreateWithoutTeamInput> | PlayerBoardCreateWithoutTeamInput[] | PlayerBoardUncheckedCreateWithoutTeamInput[]
    connectOrCreate?: PlayerBoardCreateOrConnectWithoutTeamInput | PlayerBoardCreateOrConnectWithoutTeamInput[]
    upsert?: PlayerBoardUpsertWithWhereUniqueWithoutTeamInput | PlayerBoardUpsertWithWhereUniqueWithoutTeamInput[]
    createMany?: PlayerBoardCreateManyTeamInputEnvelope
    set?: PlayerBoardWhereUniqueInput | PlayerBoardWhereUniqueInput[]
    disconnect?: PlayerBoardWhereUniqueInput | PlayerBoardWhereUniqueInput[]
    delete?: PlayerBoardWhereUniqueInput | PlayerBoardWhereUniqueInput[]
    connect?: PlayerBoardWhereUniqueInput | PlayerBoardWhereUniqueInput[]
    update?: PlayerBoardUpdateWithWhereUniqueWithoutTeamInput | PlayerBoardUpdateWithWhereUniqueWithoutTeamInput[]
    updateMany?: PlayerBoardUpdateManyWithWhereWithoutTeamInput | PlayerBoardUpdateManyWithWhereWithoutTeamInput[]
    deleteMany?: PlayerBoardScalarWhereInput | PlayerBoardScalarWhereInput[]
  }

  export type TeamMemberUncheckedUpdateManyWithoutTeamNestedInput = {
    create?: XOR<TeamMemberCreateWithoutTeamInput, TeamMemberUncheckedCreateWithoutTeamInput> | TeamMemberCreateWithoutTeamInput[] | TeamMemberUncheckedCreateWithoutTeamInput[]
    connectOrCreate?: TeamMemberCreateOrConnectWithoutTeamInput | TeamMemberCreateOrConnectWithoutTeamInput[]
    upsert?: TeamMemberUpsertWithWhereUniqueWithoutTeamInput | TeamMemberUpsertWithWhereUniqueWithoutTeamInput[]
    createMany?: TeamMemberCreateManyTeamInputEnvelope
    set?: TeamMemberWhereUniqueInput | TeamMemberWhereUniqueInput[]
    disconnect?: TeamMemberWhereUniqueInput | TeamMemberWhereUniqueInput[]
    delete?: TeamMemberWhereUniqueInput | TeamMemberWhereUniqueInput[]
    connect?: TeamMemberWhereUniqueInput | TeamMemberWhereUniqueInput[]
    update?: TeamMemberUpdateWithWhereUniqueWithoutTeamInput | TeamMemberUpdateWithWhereUniqueWithoutTeamInput[]
    updateMany?: TeamMemberUpdateManyWithWhereWithoutTeamInput | TeamMemberUpdateManyWithWhereWithoutTeamInput[]
    deleteMany?: TeamMemberScalarWhereInput | TeamMemberScalarWhereInput[]
  }

  export type BoardTeamUncheckedUpdateManyWithoutTeamNestedInput = {
    create?: XOR<BoardTeamCreateWithoutTeamInput, BoardTeamUncheckedCreateWithoutTeamInput> | BoardTeamCreateWithoutTeamInput[] | BoardTeamUncheckedCreateWithoutTeamInput[]
    connectOrCreate?: BoardTeamCreateOrConnectWithoutTeamInput | BoardTeamCreateOrConnectWithoutTeamInput[]
    upsert?: BoardTeamUpsertWithWhereUniqueWithoutTeamInput | BoardTeamUpsertWithWhereUniqueWithoutTeamInput[]
    createMany?: BoardTeamCreateManyTeamInputEnvelope
    set?: BoardTeamWhereUniqueInput | BoardTeamWhereUniqueInput[]
    disconnect?: BoardTeamWhereUniqueInput | BoardTeamWhereUniqueInput[]
    delete?: BoardTeamWhereUniqueInput | BoardTeamWhereUniqueInput[]
    connect?: BoardTeamWhereUniqueInput | BoardTeamWhereUniqueInput[]
    update?: BoardTeamUpdateWithWhereUniqueWithoutTeamInput | BoardTeamUpdateWithWhereUniqueWithoutTeamInput[]
    updateMany?: BoardTeamUpdateManyWithWhereWithoutTeamInput | BoardTeamUpdateManyWithWhereWithoutTeamInput[]
    deleteMany?: BoardTeamScalarWhereInput | BoardTeamScalarWhereInput[]
  }

  export type PlayerBoardUncheckedUpdateManyWithoutTeamNestedInput = {
    create?: XOR<PlayerBoardCreateWithoutTeamInput, PlayerBoardUncheckedCreateWithoutTeamInput> | PlayerBoardCreateWithoutTeamInput[] | PlayerBoardUncheckedCreateWithoutTeamInput[]
    connectOrCreate?: PlayerBoardCreateOrConnectWithoutTeamInput | PlayerBoardCreateOrConnectWithoutTeamInput[]
    upsert?: PlayerBoardUpsertWithWhereUniqueWithoutTeamInput | PlayerBoardUpsertWithWhereUniqueWithoutTeamInput[]
    createMany?: PlayerBoardCreateManyTeamInputEnvelope
    set?: PlayerBoardWhereUniqueInput | PlayerBoardWhereUniqueInput[]
    disconnect?: PlayerBoardWhereUniqueInput | PlayerBoardWhereUniqueInput[]
    delete?: PlayerBoardWhereUniqueInput | PlayerBoardWhereUniqueInput[]
    connect?: PlayerBoardWhereUniqueInput | PlayerBoardWhereUniqueInput[]
    update?: PlayerBoardUpdateWithWhereUniqueWithoutTeamInput | PlayerBoardUpdateWithWhereUniqueWithoutTeamInput[]
    updateMany?: PlayerBoardUpdateManyWithWhereWithoutTeamInput | PlayerBoardUpdateManyWithWhereWithoutTeamInput[]
    deleteMany?: PlayerBoardScalarWhereInput | PlayerBoardScalarWhereInput[]
  }

  export type TeamCreateNestedOneWithoutMembersInput = {
    create?: XOR<TeamCreateWithoutMembersInput, TeamUncheckedCreateWithoutMembersInput>
    connectOrCreate?: TeamCreateOrConnectWithoutMembersInput
    connect?: TeamWhereUniqueInput
  }

  export type UserCreateNestedOneWithoutTeamMembersInput = {
    create?: XOR<UserCreateWithoutTeamMembersInput, UserUncheckedCreateWithoutTeamMembersInput>
    connectOrCreate?: UserCreateOrConnectWithoutTeamMembersInput
    connect?: UserWhereUniqueInput
  }

  export type TeamUpdateOneRequiredWithoutMembersNestedInput = {
    create?: XOR<TeamCreateWithoutMembersInput, TeamUncheckedCreateWithoutMembersInput>
    connectOrCreate?: TeamCreateOrConnectWithoutMembersInput
    upsert?: TeamUpsertWithoutMembersInput
    connect?: TeamWhereUniqueInput
    update?: XOR<XOR<TeamUpdateToOneWithWhereWithoutMembersInput, TeamUpdateWithoutMembersInput>, TeamUncheckedUpdateWithoutMembersInput>
  }

  export type UserUpdateOneRequiredWithoutTeamMembersNestedInput = {
    create?: XOR<UserCreateWithoutTeamMembersInput, UserUncheckedCreateWithoutTeamMembersInput>
    connectOrCreate?: UserCreateOrConnectWithoutTeamMembersInput
    upsert?: UserUpsertWithoutTeamMembersInput
    connect?: UserWhereUniqueInput
    update?: XOR<XOR<UserUpdateToOneWithWhereWithoutTeamMembersInput, UserUpdateWithoutTeamMembersInput>, UserUncheckedUpdateWithoutTeamMembersInput>
  }

  export type NestedUuidFilter<$PrismaModel = never> = {
    equals?: string | StringFieldRefInput<$PrismaModel>
    in?: string[] | ListStringFieldRefInput<$PrismaModel>
    notIn?: string[] | ListStringFieldRefInput<$PrismaModel>
    lt?: string | StringFieldRefInput<$PrismaModel>
    lte?: string | StringFieldRefInput<$PrismaModel>
    gt?: string | StringFieldRefInput<$PrismaModel>
    gte?: string | StringFieldRefInput<$PrismaModel>
    not?: NestedUuidFilter<$PrismaModel> | string
  }

  export type NestedStringFilter<$PrismaModel = never> = {
    equals?: string | StringFieldRefInput<$PrismaModel>
    in?: string[] | ListStringFieldRefInput<$PrismaModel>
    notIn?: string[] | ListStringFieldRefInput<$PrismaModel>
    lt?: string | StringFieldRefInput<$PrismaModel>
    lte?: string | StringFieldRefInput<$PrismaModel>
    gt?: string | StringFieldRefInput<$PrismaModel>
    gte?: string | StringFieldRefInput<$PrismaModel>
    contains?: string | StringFieldRefInput<$PrismaModel>
    startsWith?: string | StringFieldRefInput<$PrismaModel>
    endsWith?: string | StringFieldRefInput<$PrismaModel>
    not?: NestedStringFilter<$PrismaModel> | string
  }

  export type NestedStringNullableFilter<$PrismaModel = never> = {
    equals?: string | StringFieldRefInput<$PrismaModel> | null
    in?: string[] | ListStringFieldRefInput<$PrismaModel> | null
    notIn?: string[] | ListStringFieldRefInput<$PrismaModel> | null
    lt?: string | StringFieldRefInput<$PrismaModel>
    lte?: string | StringFieldRefInput<$PrismaModel>
    gt?: string | StringFieldRefInput<$PrismaModel>
    gte?: string | StringFieldRefInput<$PrismaModel>
    contains?: string | StringFieldRefInput<$PrismaModel>
    startsWith?: string | StringFieldRefInput<$PrismaModel>
    endsWith?: string | StringFieldRefInput<$PrismaModel>
    not?: NestedStringNullableFilter<$PrismaModel> | string | null
  }

  export type NestedDateTimeFilter<$PrismaModel = never> = {
    equals?: Date | string | DateTimeFieldRefInput<$PrismaModel>
    in?: Date[] | string[] | ListDateTimeFieldRefInput<$PrismaModel>
    notIn?: Date[] | string[] | ListDateTimeFieldRefInput<$PrismaModel>
    lt?: Date | string | DateTimeFieldRefInput<$PrismaModel>
    lte?: Date | string | DateTimeFieldRefInput<$PrismaModel>
    gt?: Date | string | DateTimeFieldRefInput<$PrismaModel>
    gte?: Date | string | DateTimeFieldRefInput<$PrismaModel>
    not?: NestedDateTimeFilter<$PrismaModel> | Date | string
  }

  export type NestedUuidWithAggregatesFilter<$PrismaModel = never> = {
    equals?: string | StringFieldRefInput<$PrismaModel>
    in?: string[] | ListStringFieldRefInput<$PrismaModel>
    notIn?: string[] | ListStringFieldRefInput<$PrismaModel>
    lt?: string | StringFieldRefInput<$PrismaModel>
    lte?: string | StringFieldRefInput<$PrismaModel>
    gt?: string | StringFieldRefInput<$PrismaModel>
    gte?: string | StringFieldRefInput<$PrismaModel>
    not?: NestedUuidWithAggregatesFilter<$PrismaModel> | string
    _count?: NestedIntFilter<$PrismaModel>
    _min?: NestedStringFilter<$PrismaModel>
    _max?: NestedStringFilter<$PrismaModel>
  }

  export type NestedIntFilter<$PrismaModel = never> = {
    equals?: number | IntFieldRefInput<$PrismaModel>
    in?: number[] | ListIntFieldRefInput<$PrismaModel>
    notIn?: number[] | ListIntFieldRefInput<$PrismaModel>
    lt?: number | IntFieldRefInput<$PrismaModel>
    lte?: number | IntFieldRefInput<$PrismaModel>
    gt?: number | IntFieldRefInput<$PrismaModel>
    gte?: number | IntFieldRefInput<$PrismaModel>
    not?: NestedIntFilter<$PrismaModel> | number
  }

  export type NestedStringWithAggregatesFilter<$PrismaModel = never> = {
    equals?: string | StringFieldRefInput<$PrismaModel>
    in?: string[] | ListStringFieldRefInput<$PrismaModel>
    notIn?: string[] | ListStringFieldRefInput<$PrismaModel>
    lt?: string | StringFieldRefInput<$PrismaModel>
    lte?: string | StringFieldRefInput<$PrismaModel>
    gt?: string | StringFieldRefInput<$PrismaModel>
    gte?: string | StringFieldRefInput<$PrismaModel>
    contains?: string | StringFieldRefInput<$PrismaModel>
    startsWith?: string | StringFieldRefInput<$PrismaModel>
    endsWith?: string | StringFieldRefInput<$PrismaModel>
    not?: NestedStringWithAggregatesFilter<$PrismaModel> | string
    _count?: NestedIntFilter<$PrismaModel>
    _min?: NestedStringFilter<$PrismaModel>
    _max?: NestedStringFilter<$PrismaModel>
  }

  export type NestedStringNullableWithAggregatesFilter<$PrismaModel = never> = {
    equals?: string | StringFieldRefInput<$PrismaModel> | null
    in?: string[] | ListStringFieldRefInput<$PrismaModel> | null
    notIn?: string[] | ListStringFieldRefInput<$PrismaModel> | null
    lt?: string | StringFieldRefInput<$PrismaModel>
    lte?: string | StringFieldRefInput<$PrismaModel>
    gt?: string | StringFieldRefInput<$PrismaModel>
    gte?: string | StringFieldRefInput<$PrismaModel>
    contains?: string | StringFieldRefInput<$PrismaModel>
    startsWith?: string | StringFieldRefInput<$PrismaModel>
    endsWith?: string | StringFieldRefInput<$PrismaModel>
    not?: NestedStringNullableWithAggregatesFilter<$PrismaModel> | string | null
    _count?: NestedIntNullableFilter<$PrismaModel>
    _min?: NestedStringNullableFilter<$PrismaModel>
    _max?: NestedStringNullableFilter<$PrismaModel>
  }

  export type NestedIntNullableFilter<$PrismaModel = never> = {
    equals?: number | IntFieldRefInput<$PrismaModel> | null
    in?: number[] | ListIntFieldRefInput<$PrismaModel> | null
    notIn?: number[] | ListIntFieldRefInput<$PrismaModel> | null
    lt?: number | IntFieldRefInput<$PrismaModel>
    lte?: number | IntFieldRefInput<$PrismaModel>
    gt?: number | IntFieldRefInput<$PrismaModel>
    gte?: number | IntFieldRefInput<$PrismaModel>
    not?: NestedIntNullableFilter<$PrismaModel> | number | null
  }

  export type NestedDateTimeWithAggregatesFilter<$PrismaModel = never> = {
    equals?: Date | string | DateTimeFieldRefInput<$PrismaModel>
    in?: Date[] | string[] | ListDateTimeFieldRefInput<$PrismaModel>
    notIn?: Date[] | string[] | ListDateTimeFieldRefInput<$PrismaModel>
    lt?: Date | string | DateTimeFieldRefInput<$PrismaModel>
    lte?: Date | string | DateTimeFieldRefInput<$PrismaModel>
    gt?: Date | string | DateTimeFieldRefInput<$PrismaModel>
    gte?: Date | string | DateTimeFieldRefInput<$PrismaModel>
    not?: NestedDateTimeWithAggregatesFilter<$PrismaModel> | Date | string
    _count?: NestedIntFilter<$PrismaModel>
    _min?: NestedDateTimeFilter<$PrismaModel>
    _max?: NestedDateTimeFilter<$PrismaModel>
  }

  export type NestedDateTimeNullableFilter<$PrismaModel = never> = {
    equals?: Date | string | DateTimeFieldRefInput<$PrismaModel> | null
    in?: Date[] | string[] | ListDateTimeFieldRefInput<$PrismaModel> | null
    notIn?: Date[] | string[] | ListDateTimeFieldRefInput<$PrismaModel> | null
    lt?: Date | string | DateTimeFieldRefInput<$PrismaModel>
    lte?: Date | string | DateTimeFieldRefInput<$PrismaModel>
    gt?: Date | string | DateTimeFieldRefInput<$PrismaModel>
    gte?: Date | string | DateTimeFieldRefInput<$PrismaModel>
    not?: NestedDateTimeNullableFilter<$PrismaModel> | Date | string | null
  }

  export type NestedEnumBoardSizeFilter<$PrismaModel = never> = {
    equals?: $Enums.BoardSize | EnumBoardSizeFieldRefInput<$PrismaModel>
    in?: $Enums.BoardSize[] | ListEnumBoardSizeFieldRefInput<$PrismaModel>
    notIn?: $Enums.BoardSize[] | ListEnumBoardSizeFieldRefInput<$PrismaModel>
    not?: NestedEnumBoardSizeFilter<$PrismaModel> | $Enums.BoardSize
  }

  export type NestedEnumBoardModeFilter<$PrismaModel = never> = {
    equals?: $Enums.BoardMode | EnumBoardModeFieldRefInput<$PrismaModel>
    in?: $Enums.BoardMode[] | ListEnumBoardModeFieldRefInput<$PrismaModel>
    notIn?: $Enums.BoardMode[] | ListEnumBoardModeFieldRefInput<$PrismaModel>
    not?: NestedEnumBoardModeFilter<$PrismaModel> | $Enums.BoardMode
  }

  export type NestedDateTimeNullableWithAggregatesFilter<$PrismaModel = never> = {
    equals?: Date | string | DateTimeFieldRefInput<$PrismaModel> | null
    in?: Date[] | string[] | ListDateTimeFieldRefInput<$PrismaModel> | null
    notIn?: Date[] | string[] | ListDateTimeFieldRefInput<$PrismaModel> | null
    lt?: Date | string | DateTimeFieldRefInput<$PrismaModel>
    lte?: Date | string | DateTimeFieldRefInput<$PrismaModel>
    gt?: Date | string | DateTimeFieldRefInput<$PrismaModel>
    gte?: Date | string | DateTimeFieldRefInput<$PrismaModel>
    not?: NestedDateTimeNullableWithAggregatesFilter<$PrismaModel> | Date | string | null
    _count?: NestedIntNullableFilter<$PrismaModel>
    _min?: NestedDateTimeNullableFilter<$PrismaModel>
    _max?: NestedDateTimeNullableFilter<$PrismaModel>
  }

  export type NestedEnumBoardSizeWithAggregatesFilter<$PrismaModel = never> = {
    equals?: $Enums.BoardSize | EnumBoardSizeFieldRefInput<$PrismaModel>
    in?: $Enums.BoardSize[] | ListEnumBoardSizeFieldRefInput<$PrismaModel>
    notIn?: $Enums.BoardSize[] | ListEnumBoardSizeFieldRefInput<$PrismaModel>
    not?: NestedEnumBoardSizeWithAggregatesFilter<$PrismaModel> | $Enums.BoardSize
    _count?: NestedIntFilter<$PrismaModel>
    _min?: NestedEnumBoardSizeFilter<$PrismaModel>
    _max?: NestedEnumBoardSizeFilter<$PrismaModel>
  }

  export type NestedEnumBoardModeWithAggregatesFilter<$PrismaModel = never> = {
    equals?: $Enums.BoardMode | EnumBoardModeFieldRefInput<$PrismaModel>
    in?: $Enums.BoardMode[] | ListEnumBoardModeFieldRefInput<$PrismaModel>
    notIn?: $Enums.BoardMode[] | ListEnumBoardModeFieldRefInput<$PrismaModel>
    not?: NestedEnumBoardModeWithAggregatesFilter<$PrismaModel> | $Enums.BoardMode
    _count?: NestedIntFilter<$PrismaModel>
    _min?: NestedEnumBoardModeFilter<$PrismaModel>
    _max?: NestedEnumBoardModeFilter<$PrismaModel>
  }

  export type NestedIntNullableWithAggregatesFilter<$PrismaModel = never> = {
    equals?: number | IntFieldRefInput<$PrismaModel> | null
    in?: number[] | ListIntFieldRefInput<$PrismaModel> | null
    notIn?: number[] | ListIntFieldRefInput<$PrismaModel> | null
    lt?: number | IntFieldRefInput<$PrismaModel>
    lte?: number | IntFieldRefInput<$PrismaModel>
    gt?: number | IntFieldRefInput<$PrismaModel>
    gte?: number | IntFieldRefInput<$PrismaModel>
    not?: NestedIntNullableWithAggregatesFilter<$PrismaModel> | number | null
    _count?: NestedIntNullableFilter<$PrismaModel>
    _avg?: NestedFloatNullableFilter<$PrismaModel>
    _sum?: NestedIntNullableFilter<$PrismaModel>
    _min?: NestedIntNullableFilter<$PrismaModel>
    _max?: NestedIntNullableFilter<$PrismaModel>
  }

  export type NestedFloatNullableFilter<$PrismaModel = never> = {
    equals?: number | FloatFieldRefInput<$PrismaModel> | null
    in?: number[] | ListFloatFieldRefInput<$PrismaModel> | null
    notIn?: number[] | ListFloatFieldRefInput<$PrismaModel> | null
    lt?: number | FloatFieldRefInput<$PrismaModel>
    lte?: number | FloatFieldRefInput<$PrismaModel>
    gt?: number | FloatFieldRefInput<$PrismaModel>
    gte?: number | FloatFieldRefInput<$PrismaModel>
    not?: NestedFloatNullableFilter<$PrismaModel> | number | null
  }

  export type NestedBoolFilter<$PrismaModel = never> = {
    equals?: boolean | BooleanFieldRefInput<$PrismaModel>
    not?: NestedBoolFilter<$PrismaModel> | boolean
  }

  export type NestedBoolWithAggregatesFilter<$PrismaModel = never> = {
    equals?: boolean | BooleanFieldRefInput<$PrismaModel>
    not?: NestedBoolWithAggregatesFilter<$PrismaModel> | boolean
    _count?: NestedIntFilter<$PrismaModel>
    _min?: NestedBoolFilter<$PrismaModel>
    _max?: NestedBoolFilter<$PrismaModel>
  }

  export type NestedUuidNullableFilter<$PrismaModel = never> = {
    equals?: string | StringFieldRefInput<$PrismaModel> | null
    in?: string[] | ListStringFieldRefInput<$PrismaModel> | null
    notIn?: string[] | ListStringFieldRefInput<$PrismaModel> | null
    lt?: string | StringFieldRefInput<$PrismaModel>
    lte?: string | StringFieldRefInput<$PrismaModel>
    gt?: string | StringFieldRefInput<$PrismaModel>
    gte?: string | StringFieldRefInput<$PrismaModel>
    not?: NestedUuidNullableFilter<$PrismaModel> | string | null
  }

  export type NestedEnumTileTypeFilter<$PrismaModel = never> = {
    equals?: $Enums.TileType | EnumTileTypeFieldRefInput<$PrismaModel>
    in?: $Enums.TileType[] | ListEnumTileTypeFieldRefInput<$PrismaModel>
    notIn?: $Enums.TileType[] | ListEnumTileTypeFieldRefInput<$PrismaModel>
    not?: NestedEnumTileTypeFilter<$PrismaModel> | $Enums.TileType
  }

  export type NestedIntWithAggregatesFilter<$PrismaModel = never> = {
    equals?: number | IntFieldRefInput<$PrismaModel>
    in?: number[] | ListIntFieldRefInput<$PrismaModel>
    notIn?: number[] | ListIntFieldRefInput<$PrismaModel>
    lt?: number | IntFieldRefInput<$PrismaModel>
    lte?: number | IntFieldRefInput<$PrismaModel>
    gt?: number | IntFieldRefInput<$PrismaModel>
    gte?: number | IntFieldRefInput<$PrismaModel>
    not?: NestedIntWithAggregatesFilter<$PrismaModel> | number
    _count?: NestedIntFilter<$PrismaModel>
    _avg?: NestedFloatFilter<$PrismaModel>
    _sum?: NestedIntFilter<$PrismaModel>
    _min?: NestedIntFilter<$PrismaModel>
    _max?: NestedIntFilter<$PrismaModel>
  }

  export type NestedFloatFilter<$PrismaModel = never> = {
    equals?: number | FloatFieldRefInput<$PrismaModel>
    in?: number[] | ListFloatFieldRefInput<$PrismaModel>
    notIn?: number[] | ListFloatFieldRefInput<$PrismaModel>
    lt?: number | FloatFieldRefInput<$PrismaModel>
    lte?: number | FloatFieldRefInput<$PrismaModel>
    gt?: number | FloatFieldRefInput<$PrismaModel>
    gte?: number | FloatFieldRefInput<$PrismaModel>
    not?: NestedFloatFilter<$PrismaModel> | number
  }

  export type NestedUuidNullableWithAggregatesFilter<$PrismaModel = never> = {
    equals?: string | StringFieldRefInput<$PrismaModel> | null
    in?: string[] | ListStringFieldRefInput<$PrismaModel> | null
    notIn?: string[] | ListStringFieldRefInput<$PrismaModel> | null
    lt?: string | StringFieldRefInput<$PrismaModel>
    lte?: string | StringFieldRefInput<$PrismaModel>
    gt?: string | StringFieldRefInput<$PrismaModel>
    gte?: string | StringFieldRefInput<$PrismaModel>
    not?: NestedUuidNullableWithAggregatesFilter<$PrismaModel> | string | null
    _count?: NestedIntNullableFilter<$PrismaModel>
    _min?: NestedStringNullableFilter<$PrismaModel>
    _max?: NestedStringNullableFilter<$PrismaModel>
  }

  export type NestedEnumTileTypeWithAggregatesFilter<$PrismaModel = never> = {
    equals?: $Enums.TileType | EnumTileTypeFieldRefInput<$PrismaModel>
    in?: $Enums.TileType[] | ListEnumTileTypeFieldRefInput<$PrismaModel>
    notIn?: $Enums.TileType[] | ListEnumTileTypeFieldRefInput<$PrismaModel>
    not?: NestedEnumTileTypeWithAggregatesFilter<$PrismaModel> | $Enums.TileType
    _count?: NestedIntFilter<$PrismaModel>
    _min?: NestedEnumTileTypeFilter<$PrismaModel>
    _max?: NestedEnumTileTypeFilter<$PrismaModel>
  }

  export type NestedEnumCompletionSourceFilter<$PrismaModel = never> = {
    equals?: $Enums.CompletionSource | EnumCompletionSourceFieldRefInput<$PrismaModel>
    in?: $Enums.CompletionSource[] | ListEnumCompletionSourceFieldRefInput<$PrismaModel>
    notIn?: $Enums.CompletionSource[] | ListEnumCompletionSourceFieldRefInput<$PrismaModel>
    not?: NestedEnumCompletionSourceFilter<$PrismaModel> | $Enums.CompletionSource
  }

  export type NestedEnumCompletionSourceWithAggregatesFilter<$PrismaModel = never> = {
    equals?: $Enums.CompletionSource | EnumCompletionSourceFieldRefInput<$PrismaModel>
    in?: $Enums.CompletionSource[] | ListEnumCompletionSourceFieldRefInput<$PrismaModel>
    notIn?: $Enums.CompletionSource[] | ListEnumCompletionSourceFieldRefInput<$PrismaModel>
    not?: NestedEnumCompletionSourceWithAggregatesFilter<$PrismaModel> | $Enums.CompletionSource
    _count?: NestedIntFilter<$PrismaModel>
    _min?: NestedEnumCompletionSourceFilter<$PrismaModel>
    _max?: NestedEnumCompletionSourceFilter<$PrismaModel>
  }

  export type UserRoleCreateWithoutRoleInput = {
    id?: string
    createdAt?: Date | string
    user: UserCreateNestedOneWithoutUserRolesInput
  }

  export type UserRoleUncheckedCreateWithoutRoleInput = {
    id?: string
    userId: string
    createdAt?: Date | string
  }

  export type UserRoleCreateOrConnectWithoutRoleInput = {
    where: UserRoleWhereUniqueInput
    create: XOR<UserRoleCreateWithoutRoleInput, UserRoleUncheckedCreateWithoutRoleInput>
  }

  export type UserRoleCreateManyRoleInputEnvelope = {
    data: UserRoleCreateManyRoleInput | UserRoleCreateManyRoleInput[]
    skipDuplicates?: boolean
  }

  export type UserRoleUpsertWithWhereUniqueWithoutRoleInput = {
    where: UserRoleWhereUniqueInput
    update: XOR<UserRoleUpdateWithoutRoleInput, UserRoleUncheckedUpdateWithoutRoleInput>
    create: XOR<UserRoleCreateWithoutRoleInput, UserRoleUncheckedCreateWithoutRoleInput>
  }

  export type UserRoleUpdateWithWhereUniqueWithoutRoleInput = {
    where: UserRoleWhereUniqueInput
    data: XOR<UserRoleUpdateWithoutRoleInput, UserRoleUncheckedUpdateWithoutRoleInput>
  }

  export type UserRoleUpdateManyWithWhereWithoutRoleInput = {
    where: UserRoleScalarWhereInput
    data: XOR<UserRoleUpdateManyMutationInput, UserRoleUncheckedUpdateManyWithoutRoleInput>
  }

  export type UserRoleScalarWhereInput = {
    AND?: UserRoleScalarWhereInput | UserRoleScalarWhereInput[]
    OR?: UserRoleScalarWhereInput[]
    NOT?: UserRoleScalarWhereInput | UserRoleScalarWhereInput[]
    id?: UuidFilter<"UserRole"> | string
    userId?: UuidFilter<"UserRole"> | string
    roleId?: UuidFilter<"UserRole"> | string
    createdAt?: DateTimeFilter<"UserRole"> | Date | string
  }

  export type UserRoleCreateWithoutUserInput = {
    id?: string
    createdAt?: Date | string
    role: RoleCreateNestedOneWithoutUserRolesInput
  }

  export type UserRoleUncheckedCreateWithoutUserInput = {
    id?: string
    roleId: string
    createdAt?: Date | string
  }

  export type UserRoleCreateOrConnectWithoutUserInput = {
    where: UserRoleWhereUniqueInput
    create: XOR<UserRoleCreateWithoutUserInput, UserRoleUncheckedCreateWithoutUserInput>
  }

  export type UserRoleCreateManyUserInputEnvelope = {
    data: UserRoleCreateManyUserInput | UserRoleCreateManyUserInput[]
    skipDuplicates?: boolean
  }

  export type UserPermissionCreateWithoutUserInput = {
    id?: string
    permissionKey: string
    createdAt?: Date | string
  }

  export type UserPermissionUncheckedCreateWithoutUserInput = {
    id?: string
    permissionKey: string
    createdAt?: Date | string
  }

  export type UserPermissionCreateOrConnectWithoutUserInput = {
    where: UserPermissionWhereUniqueInput
    create: XOR<UserPermissionCreateWithoutUserInput, UserPermissionUncheckedCreateWithoutUserInput>
  }

  export type UserPermissionCreateManyUserInputEnvelope = {
    data: UserPermissionCreateManyUserInput | UserPermissionCreateManyUserInput[]
    skipDuplicates?: boolean
  }

  export type BoardAuthorCreateWithoutUserInput = {
    id?: string
    isOwner?: boolean
    createdAt?: Date | string
    board: BoardCreateNestedOneWithoutAuthorsInput
  }

  export type BoardAuthorUncheckedCreateWithoutUserInput = {
    id?: string
    boardId: string
    isOwner?: boolean
    createdAt?: Date | string
  }

  export type BoardAuthorCreateOrConnectWithoutUserInput = {
    where: BoardAuthorWhereUniqueInput
    create: XOR<BoardAuthorCreateWithoutUserInput, BoardAuthorUncheckedCreateWithoutUserInput>
  }

  export type BoardAuthorCreateManyUserInputEnvelope = {
    data: BoardAuthorCreateManyUserInput | BoardAuthorCreateManyUserInput[]
    skipDuplicates?: boolean
  }

  export type PlayerBoardCreateWithoutUserInput = {
    id?: string
    currentPosition?: number
    diceRollsToday?: number
    lastRollDate?: Date | string | null
    createdAt?: Date | string
    updatedAt?: Date | string
    board: BoardCreateNestedOneWithoutPlayerBoardsInput
    team?: TeamCreateNestedOneWithoutPlayerBoardsInput
    completedTiles?: CompletedTileCreateNestedManyWithoutPlayerBoardInput
  }

  export type PlayerBoardUncheckedCreateWithoutUserInput = {
    id?: string
    boardId: string
    teamId?: string | null
    currentPosition?: number
    diceRollsToday?: number
    lastRollDate?: Date | string | null
    createdAt?: Date | string
    updatedAt?: Date | string
    completedTiles?: CompletedTileUncheckedCreateNestedManyWithoutPlayerBoardInput
  }

  export type PlayerBoardCreateOrConnectWithoutUserInput = {
    where: PlayerBoardWhereUniqueInput
    create: XOR<PlayerBoardCreateWithoutUserInput, PlayerBoardUncheckedCreateWithoutUserInput>
  }

  export type PlayerBoardCreateManyUserInputEnvelope = {
    data: PlayerBoardCreateManyUserInput | PlayerBoardCreateManyUserInput[]
    skipDuplicates?: boolean
  }

  export type TeamMemberCreateWithoutUserInput = {
    id?: string
    createdAt?: Date | string
    team: TeamCreateNestedOneWithoutMembersInput
  }

  export type TeamMemberUncheckedCreateWithoutUserInput = {
    id?: string
    teamId: string
    createdAt?: Date | string
  }

  export type TeamMemberCreateOrConnectWithoutUserInput = {
    where: TeamMemberWhereUniqueInput
    create: XOR<TeamMemberCreateWithoutUserInput, TeamMemberUncheckedCreateWithoutUserInput>
  }

  export type TeamMemberCreateManyUserInputEnvelope = {
    data: TeamMemberCreateManyUserInput | TeamMemberCreateManyUserInput[]
    skipDuplicates?: boolean
  }

  export type UserRoleUpsertWithWhereUniqueWithoutUserInput = {
    where: UserRoleWhereUniqueInput
    update: XOR<UserRoleUpdateWithoutUserInput, UserRoleUncheckedUpdateWithoutUserInput>
    create: XOR<UserRoleCreateWithoutUserInput, UserRoleUncheckedCreateWithoutUserInput>
  }

  export type UserRoleUpdateWithWhereUniqueWithoutUserInput = {
    where: UserRoleWhereUniqueInput
    data: XOR<UserRoleUpdateWithoutUserInput, UserRoleUncheckedUpdateWithoutUserInput>
  }

  export type UserRoleUpdateManyWithWhereWithoutUserInput = {
    where: UserRoleScalarWhereInput
    data: XOR<UserRoleUpdateManyMutationInput, UserRoleUncheckedUpdateManyWithoutUserInput>
  }

  export type UserPermissionUpsertWithWhereUniqueWithoutUserInput = {
    where: UserPermissionWhereUniqueInput
    update: XOR<UserPermissionUpdateWithoutUserInput, UserPermissionUncheckedUpdateWithoutUserInput>
    create: XOR<UserPermissionCreateWithoutUserInput, UserPermissionUncheckedCreateWithoutUserInput>
  }

  export type UserPermissionUpdateWithWhereUniqueWithoutUserInput = {
    where: UserPermissionWhereUniqueInput
    data: XOR<UserPermissionUpdateWithoutUserInput, UserPermissionUncheckedUpdateWithoutUserInput>
  }

  export type UserPermissionUpdateManyWithWhereWithoutUserInput = {
    where: UserPermissionScalarWhereInput
    data: XOR<UserPermissionUpdateManyMutationInput, UserPermissionUncheckedUpdateManyWithoutUserInput>
  }

  export type UserPermissionScalarWhereInput = {
    AND?: UserPermissionScalarWhereInput | UserPermissionScalarWhereInput[]
    OR?: UserPermissionScalarWhereInput[]
    NOT?: UserPermissionScalarWhereInput | UserPermissionScalarWhereInput[]
    id?: UuidFilter<"UserPermission"> | string
    userId?: UuidFilter<"UserPermission"> | string
    permissionKey?: StringFilter<"UserPermission"> | string
    createdAt?: DateTimeFilter<"UserPermission"> | Date | string
  }

  export type BoardAuthorUpsertWithWhereUniqueWithoutUserInput = {
    where: BoardAuthorWhereUniqueInput
    update: XOR<BoardAuthorUpdateWithoutUserInput, BoardAuthorUncheckedUpdateWithoutUserInput>
    create: XOR<BoardAuthorCreateWithoutUserInput, BoardAuthorUncheckedCreateWithoutUserInput>
  }

  export type BoardAuthorUpdateWithWhereUniqueWithoutUserInput = {
    where: BoardAuthorWhereUniqueInput
    data: XOR<BoardAuthorUpdateWithoutUserInput, BoardAuthorUncheckedUpdateWithoutUserInput>
  }

  export type BoardAuthorUpdateManyWithWhereWithoutUserInput = {
    where: BoardAuthorScalarWhereInput
    data: XOR<BoardAuthorUpdateManyMutationInput, BoardAuthorUncheckedUpdateManyWithoutUserInput>
  }

  export type BoardAuthorScalarWhereInput = {
    AND?: BoardAuthorScalarWhereInput | BoardAuthorScalarWhereInput[]
    OR?: BoardAuthorScalarWhereInput[]
    NOT?: BoardAuthorScalarWhereInput | BoardAuthorScalarWhereInput[]
    id?: UuidFilter<"BoardAuthor"> | string
    boardId?: UuidFilter<"BoardAuthor"> | string
    userId?: UuidFilter<"BoardAuthor"> | string
    isOwner?: BoolFilter<"BoardAuthor"> | boolean
    createdAt?: DateTimeFilter<"BoardAuthor"> | Date | string
  }

  export type PlayerBoardUpsertWithWhereUniqueWithoutUserInput = {
    where: PlayerBoardWhereUniqueInput
    update: XOR<PlayerBoardUpdateWithoutUserInput, PlayerBoardUncheckedUpdateWithoutUserInput>
    create: XOR<PlayerBoardCreateWithoutUserInput, PlayerBoardUncheckedCreateWithoutUserInput>
  }

  export type PlayerBoardUpdateWithWhereUniqueWithoutUserInput = {
    where: PlayerBoardWhereUniqueInput
    data: XOR<PlayerBoardUpdateWithoutUserInput, PlayerBoardUncheckedUpdateWithoutUserInput>
  }

  export type PlayerBoardUpdateManyWithWhereWithoutUserInput = {
    where: PlayerBoardScalarWhereInput
    data: XOR<PlayerBoardUpdateManyMutationInput, PlayerBoardUncheckedUpdateManyWithoutUserInput>
  }

  export type PlayerBoardScalarWhereInput = {
    AND?: PlayerBoardScalarWhereInput | PlayerBoardScalarWhereInput[]
    OR?: PlayerBoardScalarWhereInput[]
    NOT?: PlayerBoardScalarWhereInput | PlayerBoardScalarWhereInput[]
    id?: UuidFilter<"PlayerBoard"> | string
    userId?: UuidFilter<"PlayerBoard"> | string
    boardId?: UuidFilter<"PlayerBoard"> | string
    teamId?: UuidNullableFilter<"PlayerBoard"> | string | null
    currentPosition?: IntFilter<"PlayerBoard"> | number
    diceRollsToday?: IntFilter<"PlayerBoard"> | number
    lastRollDate?: DateTimeNullableFilter<"PlayerBoard"> | Date | string | null
    createdAt?: DateTimeFilter<"PlayerBoard"> | Date | string
    updatedAt?: DateTimeFilter<"PlayerBoard"> | Date | string
  }

  export type TeamMemberUpsertWithWhereUniqueWithoutUserInput = {
    where: TeamMemberWhereUniqueInput
    update: XOR<TeamMemberUpdateWithoutUserInput, TeamMemberUncheckedUpdateWithoutUserInput>
    create: XOR<TeamMemberCreateWithoutUserInput, TeamMemberUncheckedCreateWithoutUserInput>
  }

  export type TeamMemberUpdateWithWhereUniqueWithoutUserInput = {
    where: TeamMemberWhereUniqueInput
    data: XOR<TeamMemberUpdateWithoutUserInput, TeamMemberUncheckedUpdateWithoutUserInput>
  }

  export type TeamMemberUpdateManyWithWhereWithoutUserInput = {
    where: TeamMemberScalarWhereInput
    data: XOR<TeamMemberUpdateManyMutationInput, TeamMemberUncheckedUpdateManyWithoutUserInput>
  }

  export type TeamMemberScalarWhereInput = {
    AND?: TeamMemberScalarWhereInput | TeamMemberScalarWhereInput[]
    OR?: TeamMemberScalarWhereInput[]
    NOT?: TeamMemberScalarWhereInput | TeamMemberScalarWhereInput[]
    id?: UuidFilter<"TeamMember"> | string
    teamId?: UuidFilter<"TeamMember"> | string
    userId?: UuidFilter<"TeamMember"> | string
    createdAt?: DateTimeFilter<"TeamMember"> | Date | string
  }

  export type UserCreateWithoutUserPermissionsInput = {
    id?: string
    discordId: string
    discordUsername: string
    nickname?: string | null
    avatarUrl?: string | null
    createdAt?: Date | string
    updatedAt?: Date | string
    userRoles?: UserRoleCreateNestedManyWithoutUserInput
    boardAuthors?: BoardAuthorCreateNestedManyWithoutUserInput
    playerBoards?: PlayerBoardCreateNestedManyWithoutUserInput
    teamMembers?: TeamMemberCreateNestedManyWithoutUserInput
  }

  export type UserUncheckedCreateWithoutUserPermissionsInput = {
    id?: string
    discordId: string
    discordUsername: string
    nickname?: string | null
    avatarUrl?: string | null
    createdAt?: Date | string
    updatedAt?: Date | string
    userRoles?: UserRoleUncheckedCreateNestedManyWithoutUserInput
    boardAuthors?: BoardAuthorUncheckedCreateNestedManyWithoutUserInput
    playerBoards?: PlayerBoardUncheckedCreateNestedManyWithoutUserInput
    teamMembers?: TeamMemberUncheckedCreateNestedManyWithoutUserInput
  }

  export type UserCreateOrConnectWithoutUserPermissionsInput = {
    where: UserWhereUniqueInput
    create: XOR<UserCreateWithoutUserPermissionsInput, UserUncheckedCreateWithoutUserPermissionsInput>
  }

  export type UserUpsertWithoutUserPermissionsInput = {
    update: XOR<UserUpdateWithoutUserPermissionsInput, UserUncheckedUpdateWithoutUserPermissionsInput>
    create: XOR<UserCreateWithoutUserPermissionsInput, UserUncheckedCreateWithoutUserPermissionsInput>
    where?: UserWhereInput
  }

  export type UserUpdateToOneWithWhereWithoutUserPermissionsInput = {
    where?: UserWhereInput
    data: XOR<UserUpdateWithoutUserPermissionsInput, UserUncheckedUpdateWithoutUserPermissionsInput>
  }

  export type UserUpdateWithoutUserPermissionsInput = {
    id?: StringFieldUpdateOperationsInput | string
    discordId?: StringFieldUpdateOperationsInput | string
    discordUsername?: StringFieldUpdateOperationsInput | string
    nickname?: NullableStringFieldUpdateOperationsInput | string | null
    avatarUrl?: NullableStringFieldUpdateOperationsInput | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    userRoles?: UserRoleUpdateManyWithoutUserNestedInput
    boardAuthors?: BoardAuthorUpdateManyWithoutUserNestedInput
    playerBoards?: PlayerBoardUpdateManyWithoutUserNestedInput
    teamMembers?: TeamMemberUpdateManyWithoutUserNestedInput
  }

  export type UserUncheckedUpdateWithoutUserPermissionsInput = {
    id?: StringFieldUpdateOperationsInput | string
    discordId?: StringFieldUpdateOperationsInput | string
    discordUsername?: StringFieldUpdateOperationsInput | string
    nickname?: NullableStringFieldUpdateOperationsInput | string | null
    avatarUrl?: NullableStringFieldUpdateOperationsInput | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    userRoles?: UserRoleUncheckedUpdateManyWithoutUserNestedInput
    boardAuthors?: BoardAuthorUncheckedUpdateManyWithoutUserNestedInput
    playerBoards?: PlayerBoardUncheckedUpdateManyWithoutUserNestedInput
    teamMembers?: TeamMemberUncheckedUpdateManyWithoutUserNestedInput
  }

  export type UserCreateWithoutUserRolesInput = {
    id?: string
    discordId: string
    discordUsername: string
    nickname?: string | null
    avatarUrl?: string | null
    createdAt?: Date | string
    updatedAt?: Date | string
    userPermissions?: UserPermissionCreateNestedManyWithoutUserInput
    boardAuthors?: BoardAuthorCreateNestedManyWithoutUserInput
    playerBoards?: PlayerBoardCreateNestedManyWithoutUserInput
    teamMembers?: TeamMemberCreateNestedManyWithoutUserInput
  }

  export type UserUncheckedCreateWithoutUserRolesInput = {
    id?: string
    discordId: string
    discordUsername: string
    nickname?: string | null
    avatarUrl?: string | null
    createdAt?: Date | string
    updatedAt?: Date | string
    userPermissions?: UserPermissionUncheckedCreateNestedManyWithoutUserInput
    boardAuthors?: BoardAuthorUncheckedCreateNestedManyWithoutUserInput
    playerBoards?: PlayerBoardUncheckedCreateNestedManyWithoutUserInput
    teamMembers?: TeamMemberUncheckedCreateNestedManyWithoutUserInput
  }

  export type UserCreateOrConnectWithoutUserRolesInput = {
    where: UserWhereUniqueInput
    create: XOR<UserCreateWithoutUserRolesInput, UserUncheckedCreateWithoutUserRolesInput>
  }

  export type RoleCreateWithoutUserRolesInput = {
    id?: string
    name: string
    description?: string | null
    createdAt?: Date | string
    updatedAt?: Date | string
  }

  export type RoleUncheckedCreateWithoutUserRolesInput = {
    id?: string
    name: string
    description?: string | null
    createdAt?: Date | string
    updatedAt?: Date | string
  }

  export type RoleCreateOrConnectWithoutUserRolesInput = {
    where: RoleWhereUniqueInput
    create: XOR<RoleCreateWithoutUserRolesInput, RoleUncheckedCreateWithoutUserRolesInput>
  }

  export type UserUpsertWithoutUserRolesInput = {
    update: XOR<UserUpdateWithoutUserRolesInput, UserUncheckedUpdateWithoutUserRolesInput>
    create: XOR<UserCreateWithoutUserRolesInput, UserUncheckedCreateWithoutUserRolesInput>
    where?: UserWhereInput
  }

  export type UserUpdateToOneWithWhereWithoutUserRolesInput = {
    where?: UserWhereInput
    data: XOR<UserUpdateWithoutUserRolesInput, UserUncheckedUpdateWithoutUserRolesInput>
  }

  export type UserUpdateWithoutUserRolesInput = {
    id?: StringFieldUpdateOperationsInput | string
    discordId?: StringFieldUpdateOperationsInput | string
    discordUsername?: StringFieldUpdateOperationsInput | string
    nickname?: NullableStringFieldUpdateOperationsInput | string | null
    avatarUrl?: NullableStringFieldUpdateOperationsInput | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    userPermissions?: UserPermissionUpdateManyWithoutUserNestedInput
    boardAuthors?: BoardAuthorUpdateManyWithoutUserNestedInput
    playerBoards?: PlayerBoardUpdateManyWithoutUserNestedInput
    teamMembers?: TeamMemberUpdateManyWithoutUserNestedInput
  }

  export type UserUncheckedUpdateWithoutUserRolesInput = {
    id?: StringFieldUpdateOperationsInput | string
    discordId?: StringFieldUpdateOperationsInput | string
    discordUsername?: StringFieldUpdateOperationsInput | string
    nickname?: NullableStringFieldUpdateOperationsInput | string | null
    avatarUrl?: NullableStringFieldUpdateOperationsInput | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    userPermissions?: UserPermissionUncheckedUpdateManyWithoutUserNestedInput
    boardAuthors?: BoardAuthorUncheckedUpdateManyWithoutUserNestedInput
    playerBoards?: PlayerBoardUncheckedUpdateManyWithoutUserNestedInput
    teamMembers?: TeamMemberUncheckedUpdateManyWithoutUserNestedInput
  }

  export type RoleUpsertWithoutUserRolesInput = {
    update: XOR<RoleUpdateWithoutUserRolesInput, RoleUncheckedUpdateWithoutUserRolesInput>
    create: XOR<RoleCreateWithoutUserRolesInput, RoleUncheckedCreateWithoutUserRolesInput>
    where?: RoleWhereInput
  }

  export type RoleUpdateToOneWithWhereWithoutUserRolesInput = {
    where?: RoleWhereInput
    data: XOR<RoleUpdateWithoutUserRolesInput, RoleUncheckedUpdateWithoutUserRolesInput>
  }

  export type RoleUpdateWithoutUserRolesInput = {
    id?: StringFieldUpdateOperationsInput | string
    name?: StringFieldUpdateOperationsInput | string
    description?: NullableStringFieldUpdateOperationsInput | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type RoleUncheckedUpdateWithoutUserRolesInput = {
    id?: StringFieldUpdateOperationsInput | string
    name?: StringFieldUpdateOperationsInput | string
    description?: NullableStringFieldUpdateOperationsInput | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type TileCreateWithoutTaskInput = {
    id?: string
    position: number
    titleOverride?: string | null
    type?: $Enums.TileType
    targetPosition?: number | null
    createdAt?: Date | string
    updatedAt?: Date | string
    board: BoardCreateNestedOneWithoutTilesInput
    completedTiles?: CompletedTileCreateNestedManyWithoutTileInput
  }

  export type TileUncheckedCreateWithoutTaskInput = {
    id?: string
    boardId: string
    position: number
    titleOverride?: string | null
    type?: $Enums.TileType
    targetPosition?: number | null
    createdAt?: Date | string
    updatedAt?: Date | string
    completedTiles?: CompletedTileUncheckedCreateNestedManyWithoutTileInput
  }

  export type TileCreateOrConnectWithoutTaskInput = {
    where: TileWhereUniqueInput
    create: XOR<TileCreateWithoutTaskInput, TileUncheckedCreateWithoutTaskInput>
  }

  export type TileCreateManyTaskInputEnvelope = {
    data: TileCreateManyTaskInput | TileCreateManyTaskInput[]
    skipDuplicates?: boolean
  }

  export type TileUpsertWithWhereUniqueWithoutTaskInput = {
    where: TileWhereUniqueInput
    update: XOR<TileUpdateWithoutTaskInput, TileUncheckedUpdateWithoutTaskInput>
    create: XOR<TileCreateWithoutTaskInput, TileUncheckedCreateWithoutTaskInput>
  }

  export type TileUpdateWithWhereUniqueWithoutTaskInput = {
    where: TileWhereUniqueInput
    data: XOR<TileUpdateWithoutTaskInput, TileUncheckedUpdateWithoutTaskInput>
  }

  export type TileUpdateManyWithWhereWithoutTaskInput = {
    where: TileScalarWhereInput
    data: XOR<TileUpdateManyMutationInput, TileUncheckedUpdateManyWithoutTaskInput>
  }

  export type TileScalarWhereInput = {
    AND?: TileScalarWhereInput | TileScalarWhereInput[]
    OR?: TileScalarWhereInput[]
    NOT?: TileScalarWhereInput | TileScalarWhereInput[]
    id?: UuidFilter<"Tile"> | string
    boardId?: UuidFilter<"Tile"> | string
    position?: IntFilter<"Tile"> | number
    taskId?: UuidNullableFilter<"Tile"> | string | null
    titleOverride?: StringNullableFilter<"Tile"> | string | null
    type?: EnumTileTypeFilter<"Tile"> | $Enums.TileType
    targetPosition?: IntNullableFilter<"Tile"> | number | null
    createdAt?: DateTimeFilter<"Tile"> | Date | string
    updatedAt?: DateTimeFilter<"Tile"> | Date | string
  }

  export type BoardAuthorCreateWithoutBoardInput = {
    id?: string
    isOwner?: boolean
    createdAt?: Date | string
    user: UserCreateNestedOneWithoutBoardAuthorsInput
  }

  export type BoardAuthorUncheckedCreateWithoutBoardInput = {
    id?: string
    userId: string
    isOwner?: boolean
    createdAt?: Date | string
  }

  export type BoardAuthorCreateOrConnectWithoutBoardInput = {
    where: BoardAuthorWhereUniqueInput
    create: XOR<BoardAuthorCreateWithoutBoardInput, BoardAuthorUncheckedCreateWithoutBoardInput>
  }

  export type BoardAuthorCreateManyBoardInputEnvelope = {
    data: BoardAuthorCreateManyBoardInput | BoardAuthorCreateManyBoardInput[]
    skipDuplicates?: boolean
  }

  export type TileCreateWithoutBoardInput = {
    id?: string
    position: number
    titleOverride?: string | null
    type?: $Enums.TileType
    targetPosition?: number | null
    createdAt?: Date | string
    updatedAt?: Date | string
    task?: TaskCreateNestedOneWithoutTilesInput
    completedTiles?: CompletedTileCreateNestedManyWithoutTileInput
  }

  export type TileUncheckedCreateWithoutBoardInput = {
    id?: string
    position: number
    taskId?: string | null
    titleOverride?: string | null
    type?: $Enums.TileType
    targetPosition?: number | null
    createdAt?: Date | string
    updatedAt?: Date | string
    completedTiles?: CompletedTileUncheckedCreateNestedManyWithoutTileInput
  }

  export type TileCreateOrConnectWithoutBoardInput = {
    where: TileWhereUniqueInput
    create: XOR<TileCreateWithoutBoardInput, TileUncheckedCreateWithoutBoardInput>
  }

  export type TileCreateManyBoardInputEnvelope = {
    data: TileCreateManyBoardInput | TileCreateManyBoardInput[]
    skipDuplicates?: boolean
  }

  export type PlayerBoardCreateWithoutBoardInput = {
    id?: string
    currentPosition?: number
    diceRollsToday?: number
    lastRollDate?: Date | string | null
    createdAt?: Date | string
    updatedAt?: Date | string
    user: UserCreateNestedOneWithoutPlayerBoardsInput
    team?: TeamCreateNestedOneWithoutPlayerBoardsInput
    completedTiles?: CompletedTileCreateNestedManyWithoutPlayerBoardInput
  }

  export type PlayerBoardUncheckedCreateWithoutBoardInput = {
    id?: string
    userId: string
    teamId?: string | null
    currentPosition?: number
    diceRollsToday?: number
    lastRollDate?: Date | string | null
    createdAt?: Date | string
    updatedAt?: Date | string
    completedTiles?: CompletedTileUncheckedCreateNestedManyWithoutPlayerBoardInput
  }

  export type PlayerBoardCreateOrConnectWithoutBoardInput = {
    where: PlayerBoardWhereUniqueInput
    create: XOR<PlayerBoardCreateWithoutBoardInput, PlayerBoardUncheckedCreateWithoutBoardInput>
  }

  export type PlayerBoardCreateManyBoardInputEnvelope = {
    data: PlayerBoardCreateManyBoardInput | PlayerBoardCreateManyBoardInput[]
    skipDuplicates?: boolean
  }

  export type BoardTeamCreateWithoutBoardInput = {
    id?: string
    createdAt?: Date | string
    team: TeamCreateNestedOneWithoutBoardTeamsInput
  }

  export type BoardTeamUncheckedCreateWithoutBoardInput = {
    id?: string
    teamId: string
    createdAt?: Date | string
  }

  export type BoardTeamCreateOrConnectWithoutBoardInput = {
    where: BoardTeamWhereUniqueInput
    create: XOR<BoardTeamCreateWithoutBoardInput, BoardTeamUncheckedCreateWithoutBoardInput>
  }

  export type BoardTeamCreateManyBoardInputEnvelope = {
    data: BoardTeamCreateManyBoardInput | BoardTeamCreateManyBoardInput[]
    skipDuplicates?: boolean
  }

  export type BoardAuthorUpsertWithWhereUniqueWithoutBoardInput = {
    where: BoardAuthorWhereUniqueInput
    update: XOR<BoardAuthorUpdateWithoutBoardInput, BoardAuthorUncheckedUpdateWithoutBoardInput>
    create: XOR<BoardAuthorCreateWithoutBoardInput, BoardAuthorUncheckedCreateWithoutBoardInput>
  }

  export type BoardAuthorUpdateWithWhereUniqueWithoutBoardInput = {
    where: BoardAuthorWhereUniqueInput
    data: XOR<BoardAuthorUpdateWithoutBoardInput, BoardAuthorUncheckedUpdateWithoutBoardInput>
  }

  export type BoardAuthorUpdateManyWithWhereWithoutBoardInput = {
    where: BoardAuthorScalarWhereInput
    data: XOR<BoardAuthorUpdateManyMutationInput, BoardAuthorUncheckedUpdateManyWithoutBoardInput>
  }

  export type TileUpsertWithWhereUniqueWithoutBoardInput = {
    where: TileWhereUniqueInput
    update: XOR<TileUpdateWithoutBoardInput, TileUncheckedUpdateWithoutBoardInput>
    create: XOR<TileCreateWithoutBoardInput, TileUncheckedCreateWithoutBoardInput>
  }

  export type TileUpdateWithWhereUniqueWithoutBoardInput = {
    where: TileWhereUniqueInput
    data: XOR<TileUpdateWithoutBoardInput, TileUncheckedUpdateWithoutBoardInput>
  }

  export type TileUpdateManyWithWhereWithoutBoardInput = {
    where: TileScalarWhereInput
    data: XOR<TileUpdateManyMutationInput, TileUncheckedUpdateManyWithoutBoardInput>
  }

  export type PlayerBoardUpsertWithWhereUniqueWithoutBoardInput = {
    where: PlayerBoardWhereUniqueInput
    update: XOR<PlayerBoardUpdateWithoutBoardInput, PlayerBoardUncheckedUpdateWithoutBoardInput>
    create: XOR<PlayerBoardCreateWithoutBoardInput, PlayerBoardUncheckedCreateWithoutBoardInput>
  }

  export type PlayerBoardUpdateWithWhereUniqueWithoutBoardInput = {
    where: PlayerBoardWhereUniqueInput
    data: XOR<PlayerBoardUpdateWithoutBoardInput, PlayerBoardUncheckedUpdateWithoutBoardInput>
  }

  export type PlayerBoardUpdateManyWithWhereWithoutBoardInput = {
    where: PlayerBoardScalarWhereInput
    data: XOR<PlayerBoardUpdateManyMutationInput, PlayerBoardUncheckedUpdateManyWithoutBoardInput>
  }

  export type BoardTeamUpsertWithWhereUniqueWithoutBoardInput = {
    where: BoardTeamWhereUniqueInput
    update: XOR<BoardTeamUpdateWithoutBoardInput, BoardTeamUncheckedUpdateWithoutBoardInput>
    create: XOR<BoardTeamCreateWithoutBoardInput, BoardTeamUncheckedCreateWithoutBoardInput>
  }

  export type BoardTeamUpdateWithWhereUniqueWithoutBoardInput = {
    where: BoardTeamWhereUniqueInput
    data: XOR<BoardTeamUpdateWithoutBoardInput, BoardTeamUncheckedUpdateWithoutBoardInput>
  }

  export type BoardTeamUpdateManyWithWhereWithoutBoardInput = {
    where: BoardTeamScalarWhereInput
    data: XOR<BoardTeamUpdateManyMutationInput, BoardTeamUncheckedUpdateManyWithoutBoardInput>
  }

  export type BoardTeamScalarWhereInput = {
    AND?: BoardTeamScalarWhereInput | BoardTeamScalarWhereInput[]
    OR?: BoardTeamScalarWhereInput[]
    NOT?: BoardTeamScalarWhereInput | BoardTeamScalarWhereInput[]
    id?: UuidFilter<"BoardTeam"> | string
    boardId?: UuidFilter<"BoardTeam"> | string
    teamId?: UuidFilter<"BoardTeam"> | string
    createdAt?: DateTimeFilter<"BoardTeam"> | Date | string
  }

  export type BoardCreateWithoutBoardTeamsInput = {
    id?: string
    title: string
    description?: string | null
    startDate?: Date | string | null
    endDate?: Date | string | null
    size?: $Enums.BoardSize
    mode?: $Enums.BoardMode
    diceRollLimit?: number | null
    createdAt?: Date | string
    updatedAt?: Date | string
    authors?: BoardAuthorCreateNestedManyWithoutBoardInput
    tiles?: TileCreateNestedManyWithoutBoardInput
    playerBoards?: PlayerBoardCreateNestedManyWithoutBoardInput
  }

  export type BoardUncheckedCreateWithoutBoardTeamsInput = {
    id?: string
    title: string
    description?: string | null
    startDate?: Date | string | null
    endDate?: Date | string | null
    size?: $Enums.BoardSize
    mode?: $Enums.BoardMode
    diceRollLimit?: number | null
    createdAt?: Date | string
    updatedAt?: Date | string
    authors?: BoardAuthorUncheckedCreateNestedManyWithoutBoardInput
    tiles?: TileUncheckedCreateNestedManyWithoutBoardInput
    playerBoards?: PlayerBoardUncheckedCreateNestedManyWithoutBoardInput
  }

  export type BoardCreateOrConnectWithoutBoardTeamsInput = {
    where: BoardWhereUniqueInput
    create: XOR<BoardCreateWithoutBoardTeamsInput, BoardUncheckedCreateWithoutBoardTeamsInput>
  }

  export type TeamCreateWithoutBoardTeamsInput = {
    id?: string
    name: string
    iconUrl?: string | null
    createdAt?: Date | string
    updatedAt?: Date | string
    members?: TeamMemberCreateNestedManyWithoutTeamInput
    playerBoards?: PlayerBoardCreateNestedManyWithoutTeamInput
  }

  export type TeamUncheckedCreateWithoutBoardTeamsInput = {
    id?: string
    name: string
    iconUrl?: string | null
    createdAt?: Date | string
    updatedAt?: Date | string
    members?: TeamMemberUncheckedCreateNestedManyWithoutTeamInput
    playerBoards?: PlayerBoardUncheckedCreateNestedManyWithoutTeamInput
  }

  export type TeamCreateOrConnectWithoutBoardTeamsInput = {
    where: TeamWhereUniqueInput
    create: XOR<TeamCreateWithoutBoardTeamsInput, TeamUncheckedCreateWithoutBoardTeamsInput>
  }

  export type BoardUpsertWithoutBoardTeamsInput = {
    update: XOR<BoardUpdateWithoutBoardTeamsInput, BoardUncheckedUpdateWithoutBoardTeamsInput>
    create: XOR<BoardCreateWithoutBoardTeamsInput, BoardUncheckedCreateWithoutBoardTeamsInput>
    where?: BoardWhereInput
  }

  export type BoardUpdateToOneWithWhereWithoutBoardTeamsInput = {
    where?: BoardWhereInput
    data: XOR<BoardUpdateWithoutBoardTeamsInput, BoardUncheckedUpdateWithoutBoardTeamsInput>
  }

  export type BoardUpdateWithoutBoardTeamsInput = {
    id?: StringFieldUpdateOperationsInput | string
    title?: StringFieldUpdateOperationsInput | string
    description?: NullableStringFieldUpdateOperationsInput | string | null
    startDate?: NullableDateTimeFieldUpdateOperationsInput | Date | string | null
    endDate?: NullableDateTimeFieldUpdateOperationsInput | Date | string | null
    size?: EnumBoardSizeFieldUpdateOperationsInput | $Enums.BoardSize
    mode?: EnumBoardModeFieldUpdateOperationsInput | $Enums.BoardMode
    diceRollLimit?: NullableIntFieldUpdateOperationsInput | number | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    authors?: BoardAuthorUpdateManyWithoutBoardNestedInput
    tiles?: TileUpdateManyWithoutBoardNestedInput
    playerBoards?: PlayerBoardUpdateManyWithoutBoardNestedInput
  }

  export type BoardUncheckedUpdateWithoutBoardTeamsInput = {
    id?: StringFieldUpdateOperationsInput | string
    title?: StringFieldUpdateOperationsInput | string
    description?: NullableStringFieldUpdateOperationsInput | string | null
    startDate?: NullableDateTimeFieldUpdateOperationsInput | Date | string | null
    endDate?: NullableDateTimeFieldUpdateOperationsInput | Date | string | null
    size?: EnumBoardSizeFieldUpdateOperationsInput | $Enums.BoardSize
    mode?: EnumBoardModeFieldUpdateOperationsInput | $Enums.BoardMode
    diceRollLimit?: NullableIntFieldUpdateOperationsInput | number | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    authors?: BoardAuthorUncheckedUpdateManyWithoutBoardNestedInput
    tiles?: TileUncheckedUpdateManyWithoutBoardNestedInput
    playerBoards?: PlayerBoardUncheckedUpdateManyWithoutBoardNestedInput
  }

  export type TeamUpsertWithoutBoardTeamsInput = {
    update: XOR<TeamUpdateWithoutBoardTeamsInput, TeamUncheckedUpdateWithoutBoardTeamsInput>
    create: XOR<TeamCreateWithoutBoardTeamsInput, TeamUncheckedCreateWithoutBoardTeamsInput>
    where?: TeamWhereInput
  }

  export type TeamUpdateToOneWithWhereWithoutBoardTeamsInput = {
    where?: TeamWhereInput
    data: XOR<TeamUpdateWithoutBoardTeamsInput, TeamUncheckedUpdateWithoutBoardTeamsInput>
  }

  export type TeamUpdateWithoutBoardTeamsInput = {
    id?: StringFieldUpdateOperationsInput | string
    name?: StringFieldUpdateOperationsInput | string
    iconUrl?: NullableStringFieldUpdateOperationsInput | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    members?: TeamMemberUpdateManyWithoutTeamNestedInput
    playerBoards?: PlayerBoardUpdateManyWithoutTeamNestedInput
  }

  export type TeamUncheckedUpdateWithoutBoardTeamsInput = {
    id?: StringFieldUpdateOperationsInput | string
    name?: StringFieldUpdateOperationsInput | string
    iconUrl?: NullableStringFieldUpdateOperationsInput | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    members?: TeamMemberUncheckedUpdateManyWithoutTeamNestedInput
    playerBoards?: PlayerBoardUncheckedUpdateManyWithoutTeamNestedInput
  }

  export type BoardCreateWithoutAuthorsInput = {
    id?: string
    title: string
    description?: string | null
    startDate?: Date | string | null
    endDate?: Date | string | null
    size?: $Enums.BoardSize
    mode?: $Enums.BoardMode
    diceRollLimit?: number | null
    createdAt?: Date | string
    updatedAt?: Date | string
    tiles?: TileCreateNestedManyWithoutBoardInput
    playerBoards?: PlayerBoardCreateNestedManyWithoutBoardInput
    boardTeams?: BoardTeamCreateNestedManyWithoutBoardInput
  }

  export type BoardUncheckedCreateWithoutAuthorsInput = {
    id?: string
    title: string
    description?: string | null
    startDate?: Date | string | null
    endDate?: Date | string | null
    size?: $Enums.BoardSize
    mode?: $Enums.BoardMode
    diceRollLimit?: number | null
    createdAt?: Date | string
    updatedAt?: Date | string
    tiles?: TileUncheckedCreateNestedManyWithoutBoardInput
    playerBoards?: PlayerBoardUncheckedCreateNestedManyWithoutBoardInput
    boardTeams?: BoardTeamUncheckedCreateNestedManyWithoutBoardInput
  }

  export type BoardCreateOrConnectWithoutAuthorsInput = {
    where: BoardWhereUniqueInput
    create: XOR<BoardCreateWithoutAuthorsInput, BoardUncheckedCreateWithoutAuthorsInput>
  }

  export type UserCreateWithoutBoardAuthorsInput = {
    id?: string
    discordId: string
    discordUsername: string
    nickname?: string | null
    avatarUrl?: string | null
    createdAt?: Date | string
    updatedAt?: Date | string
    userRoles?: UserRoleCreateNestedManyWithoutUserInput
    userPermissions?: UserPermissionCreateNestedManyWithoutUserInput
    playerBoards?: PlayerBoardCreateNestedManyWithoutUserInput
    teamMembers?: TeamMemberCreateNestedManyWithoutUserInput
  }

  export type UserUncheckedCreateWithoutBoardAuthorsInput = {
    id?: string
    discordId: string
    discordUsername: string
    nickname?: string | null
    avatarUrl?: string | null
    createdAt?: Date | string
    updatedAt?: Date | string
    userRoles?: UserRoleUncheckedCreateNestedManyWithoutUserInput
    userPermissions?: UserPermissionUncheckedCreateNestedManyWithoutUserInput
    playerBoards?: PlayerBoardUncheckedCreateNestedManyWithoutUserInput
    teamMembers?: TeamMemberUncheckedCreateNestedManyWithoutUserInput
  }

  export type UserCreateOrConnectWithoutBoardAuthorsInput = {
    where: UserWhereUniqueInput
    create: XOR<UserCreateWithoutBoardAuthorsInput, UserUncheckedCreateWithoutBoardAuthorsInput>
  }

  export type BoardUpsertWithoutAuthorsInput = {
    update: XOR<BoardUpdateWithoutAuthorsInput, BoardUncheckedUpdateWithoutAuthorsInput>
    create: XOR<BoardCreateWithoutAuthorsInput, BoardUncheckedCreateWithoutAuthorsInput>
    where?: BoardWhereInput
  }

  export type BoardUpdateToOneWithWhereWithoutAuthorsInput = {
    where?: BoardWhereInput
    data: XOR<BoardUpdateWithoutAuthorsInput, BoardUncheckedUpdateWithoutAuthorsInput>
  }

  export type BoardUpdateWithoutAuthorsInput = {
    id?: StringFieldUpdateOperationsInput | string
    title?: StringFieldUpdateOperationsInput | string
    description?: NullableStringFieldUpdateOperationsInput | string | null
    startDate?: NullableDateTimeFieldUpdateOperationsInput | Date | string | null
    endDate?: NullableDateTimeFieldUpdateOperationsInput | Date | string | null
    size?: EnumBoardSizeFieldUpdateOperationsInput | $Enums.BoardSize
    mode?: EnumBoardModeFieldUpdateOperationsInput | $Enums.BoardMode
    diceRollLimit?: NullableIntFieldUpdateOperationsInput | number | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    tiles?: TileUpdateManyWithoutBoardNestedInput
    playerBoards?: PlayerBoardUpdateManyWithoutBoardNestedInput
    boardTeams?: BoardTeamUpdateManyWithoutBoardNestedInput
  }

  export type BoardUncheckedUpdateWithoutAuthorsInput = {
    id?: StringFieldUpdateOperationsInput | string
    title?: StringFieldUpdateOperationsInput | string
    description?: NullableStringFieldUpdateOperationsInput | string | null
    startDate?: NullableDateTimeFieldUpdateOperationsInput | Date | string | null
    endDate?: NullableDateTimeFieldUpdateOperationsInput | Date | string | null
    size?: EnumBoardSizeFieldUpdateOperationsInput | $Enums.BoardSize
    mode?: EnumBoardModeFieldUpdateOperationsInput | $Enums.BoardMode
    diceRollLimit?: NullableIntFieldUpdateOperationsInput | number | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    tiles?: TileUncheckedUpdateManyWithoutBoardNestedInput
    playerBoards?: PlayerBoardUncheckedUpdateManyWithoutBoardNestedInput
    boardTeams?: BoardTeamUncheckedUpdateManyWithoutBoardNestedInput
  }

  export type UserUpsertWithoutBoardAuthorsInput = {
    update: XOR<UserUpdateWithoutBoardAuthorsInput, UserUncheckedUpdateWithoutBoardAuthorsInput>
    create: XOR<UserCreateWithoutBoardAuthorsInput, UserUncheckedCreateWithoutBoardAuthorsInput>
    where?: UserWhereInput
  }

  export type UserUpdateToOneWithWhereWithoutBoardAuthorsInput = {
    where?: UserWhereInput
    data: XOR<UserUpdateWithoutBoardAuthorsInput, UserUncheckedUpdateWithoutBoardAuthorsInput>
  }

  export type UserUpdateWithoutBoardAuthorsInput = {
    id?: StringFieldUpdateOperationsInput | string
    discordId?: StringFieldUpdateOperationsInput | string
    discordUsername?: StringFieldUpdateOperationsInput | string
    nickname?: NullableStringFieldUpdateOperationsInput | string | null
    avatarUrl?: NullableStringFieldUpdateOperationsInput | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    userRoles?: UserRoleUpdateManyWithoutUserNestedInput
    userPermissions?: UserPermissionUpdateManyWithoutUserNestedInput
    playerBoards?: PlayerBoardUpdateManyWithoutUserNestedInput
    teamMembers?: TeamMemberUpdateManyWithoutUserNestedInput
  }

  export type UserUncheckedUpdateWithoutBoardAuthorsInput = {
    id?: StringFieldUpdateOperationsInput | string
    discordId?: StringFieldUpdateOperationsInput | string
    discordUsername?: StringFieldUpdateOperationsInput | string
    nickname?: NullableStringFieldUpdateOperationsInput | string | null
    avatarUrl?: NullableStringFieldUpdateOperationsInput | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    userRoles?: UserRoleUncheckedUpdateManyWithoutUserNestedInput
    userPermissions?: UserPermissionUncheckedUpdateManyWithoutUserNestedInput
    playerBoards?: PlayerBoardUncheckedUpdateManyWithoutUserNestedInput
    teamMembers?: TeamMemberUncheckedUpdateManyWithoutUserNestedInput
  }

  export type BoardCreateWithoutTilesInput = {
    id?: string
    title: string
    description?: string | null
    startDate?: Date | string | null
    endDate?: Date | string | null
    size?: $Enums.BoardSize
    mode?: $Enums.BoardMode
    diceRollLimit?: number | null
    createdAt?: Date | string
    updatedAt?: Date | string
    authors?: BoardAuthorCreateNestedManyWithoutBoardInput
    playerBoards?: PlayerBoardCreateNestedManyWithoutBoardInput
    boardTeams?: BoardTeamCreateNestedManyWithoutBoardInput
  }

  export type BoardUncheckedCreateWithoutTilesInput = {
    id?: string
    title: string
    description?: string | null
    startDate?: Date | string | null
    endDate?: Date | string | null
    size?: $Enums.BoardSize
    mode?: $Enums.BoardMode
    diceRollLimit?: number | null
    createdAt?: Date | string
    updatedAt?: Date | string
    authors?: BoardAuthorUncheckedCreateNestedManyWithoutBoardInput
    playerBoards?: PlayerBoardUncheckedCreateNestedManyWithoutBoardInput
    boardTeams?: BoardTeamUncheckedCreateNestedManyWithoutBoardInput
  }

  export type BoardCreateOrConnectWithoutTilesInput = {
    where: BoardWhereUniqueInput
    create: XOR<BoardCreateWithoutTilesInput, BoardUncheckedCreateWithoutTilesInput>
  }

  export type TaskCreateWithoutTilesInput = {
    id?: string
    title: string
    iconUrl?: string | null
    description?: string | null
    createdAt?: Date | string
    updatedAt?: Date | string
  }

  export type TaskUncheckedCreateWithoutTilesInput = {
    id?: string
    title: string
    iconUrl?: string | null
    description?: string | null
    createdAt?: Date | string
    updatedAt?: Date | string
  }

  export type TaskCreateOrConnectWithoutTilesInput = {
    where: TaskWhereUniqueInput
    create: XOR<TaskCreateWithoutTilesInput, TaskUncheckedCreateWithoutTilesInput>
  }

  export type CompletedTileCreateWithoutTileInput = {
    id?: string
    completedAt?: Date | string
    completedVia?: $Enums.CompletionSource
    playerBoard: PlayerBoardCreateNestedOneWithoutCompletedTilesInput
  }

  export type CompletedTileUncheckedCreateWithoutTileInput = {
    id?: string
    playerBoardId: string
    completedAt?: Date | string
    completedVia?: $Enums.CompletionSource
  }

  export type CompletedTileCreateOrConnectWithoutTileInput = {
    where: CompletedTileWhereUniqueInput
    create: XOR<CompletedTileCreateWithoutTileInput, CompletedTileUncheckedCreateWithoutTileInput>
  }

  export type CompletedTileCreateManyTileInputEnvelope = {
    data: CompletedTileCreateManyTileInput | CompletedTileCreateManyTileInput[]
    skipDuplicates?: boolean
  }

  export type BoardUpsertWithoutTilesInput = {
    update: XOR<BoardUpdateWithoutTilesInput, BoardUncheckedUpdateWithoutTilesInput>
    create: XOR<BoardCreateWithoutTilesInput, BoardUncheckedCreateWithoutTilesInput>
    where?: BoardWhereInput
  }

  export type BoardUpdateToOneWithWhereWithoutTilesInput = {
    where?: BoardWhereInput
    data: XOR<BoardUpdateWithoutTilesInput, BoardUncheckedUpdateWithoutTilesInput>
  }

  export type BoardUpdateWithoutTilesInput = {
    id?: StringFieldUpdateOperationsInput | string
    title?: StringFieldUpdateOperationsInput | string
    description?: NullableStringFieldUpdateOperationsInput | string | null
    startDate?: NullableDateTimeFieldUpdateOperationsInput | Date | string | null
    endDate?: NullableDateTimeFieldUpdateOperationsInput | Date | string | null
    size?: EnumBoardSizeFieldUpdateOperationsInput | $Enums.BoardSize
    mode?: EnumBoardModeFieldUpdateOperationsInput | $Enums.BoardMode
    diceRollLimit?: NullableIntFieldUpdateOperationsInput | number | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    authors?: BoardAuthorUpdateManyWithoutBoardNestedInput
    playerBoards?: PlayerBoardUpdateManyWithoutBoardNestedInput
    boardTeams?: BoardTeamUpdateManyWithoutBoardNestedInput
  }

  export type BoardUncheckedUpdateWithoutTilesInput = {
    id?: StringFieldUpdateOperationsInput | string
    title?: StringFieldUpdateOperationsInput | string
    description?: NullableStringFieldUpdateOperationsInput | string | null
    startDate?: NullableDateTimeFieldUpdateOperationsInput | Date | string | null
    endDate?: NullableDateTimeFieldUpdateOperationsInput | Date | string | null
    size?: EnumBoardSizeFieldUpdateOperationsInput | $Enums.BoardSize
    mode?: EnumBoardModeFieldUpdateOperationsInput | $Enums.BoardMode
    diceRollLimit?: NullableIntFieldUpdateOperationsInput | number | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    authors?: BoardAuthorUncheckedUpdateManyWithoutBoardNestedInput
    playerBoards?: PlayerBoardUncheckedUpdateManyWithoutBoardNestedInput
    boardTeams?: BoardTeamUncheckedUpdateManyWithoutBoardNestedInput
  }

  export type TaskUpsertWithoutTilesInput = {
    update: XOR<TaskUpdateWithoutTilesInput, TaskUncheckedUpdateWithoutTilesInput>
    create: XOR<TaskCreateWithoutTilesInput, TaskUncheckedCreateWithoutTilesInput>
    where?: TaskWhereInput
  }

  export type TaskUpdateToOneWithWhereWithoutTilesInput = {
    where?: TaskWhereInput
    data: XOR<TaskUpdateWithoutTilesInput, TaskUncheckedUpdateWithoutTilesInput>
  }

  export type TaskUpdateWithoutTilesInput = {
    id?: StringFieldUpdateOperationsInput | string
    title?: StringFieldUpdateOperationsInput | string
    iconUrl?: NullableStringFieldUpdateOperationsInput | string | null
    description?: NullableStringFieldUpdateOperationsInput | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type TaskUncheckedUpdateWithoutTilesInput = {
    id?: StringFieldUpdateOperationsInput | string
    title?: StringFieldUpdateOperationsInput | string
    iconUrl?: NullableStringFieldUpdateOperationsInput | string | null
    description?: NullableStringFieldUpdateOperationsInput | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type CompletedTileUpsertWithWhereUniqueWithoutTileInput = {
    where: CompletedTileWhereUniqueInput
    update: XOR<CompletedTileUpdateWithoutTileInput, CompletedTileUncheckedUpdateWithoutTileInput>
    create: XOR<CompletedTileCreateWithoutTileInput, CompletedTileUncheckedCreateWithoutTileInput>
  }

  export type CompletedTileUpdateWithWhereUniqueWithoutTileInput = {
    where: CompletedTileWhereUniqueInput
    data: XOR<CompletedTileUpdateWithoutTileInput, CompletedTileUncheckedUpdateWithoutTileInput>
  }

  export type CompletedTileUpdateManyWithWhereWithoutTileInput = {
    where: CompletedTileScalarWhereInput
    data: XOR<CompletedTileUpdateManyMutationInput, CompletedTileUncheckedUpdateManyWithoutTileInput>
  }

  export type CompletedTileScalarWhereInput = {
    AND?: CompletedTileScalarWhereInput | CompletedTileScalarWhereInput[]
    OR?: CompletedTileScalarWhereInput[]
    NOT?: CompletedTileScalarWhereInput | CompletedTileScalarWhereInput[]
    id?: UuidFilter<"CompletedTile"> | string
    playerBoardId?: UuidFilter<"CompletedTile"> | string
    tileId?: UuidFilter<"CompletedTile"> | string
    completedAt?: DateTimeFilter<"CompletedTile"> | Date | string
    completedVia?: EnumCompletionSourceFilter<"CompletedTile"> | $Enums.CompletionSource
  }

  export type UserCreateWithoutPlayerBoardsInput = {
    id?: string
    discordId: string
    discordUsername: string
    nickname?: string | null
    avatarUrl?: string | null
    createdAt?: Date | string
    updatedAt?: Date | string
    userRoles?: UserRoleCreateNestedManyWithoutUserInput
    userPermissions?: UserPermissionCreateNestedManyWithoutUserInput
    boardAuthors?: BoardAuthorCreateNestedManyWithoutUserInput
    teamMembers?: TeamMemberCreateNestedManyWithoutUserInput
  }

  export type UserUncheckedCreateWithoutPlayerBoardsInput = {
    id?: string
    discordId: string
    discordUsername: string
    nickname?: string | null
    avatarUrl?: string | null
    createdAt?: Date | string
    updatedAt?: Date | string
    userRoles?: UserRoleUncheckedCreateNestedManyWithoutUserInput
    userPermissions?: UserPermissionUncheckedCreateNestedManyWithoutUserInput
    boardAuthors?: BoardAuthorUncheckedCreateNestedManyWithoutUserInput
    teamMembers?: TeamMemberUncheckedCreateNestedManyWithoutUserInput
  }

  export type UserCreateOrConnectWithoutPlayerBoardsInput = {
    where: UserWhereUniqueInput
    create: XOR<UserCreateWithoutPlayerBoardsInput, UserUncheckedCreateWithoutPlayerBoardsInput>
  }

  export type BoardCreateWithoutPlayerBoardsInput = {
    id?: string
    title: string
    description?: string | null
    startDate?: Date | string | null
    endDate?: Date | string | null
    size?: $Enums.BoardSize
    mode?: $Enums.BoardMode
    diceRollLimit?: number | null
    createdAt?: Date | string
    updatedAt?: Date | string
    authors?: BoardAuthorCreateNestedManyWithoutBoardInput
    tiles?: TileCreateNestedManyWithoutBoardInput
    boardTeams?: BoardTeamCreateNestedManyWithoutBoardInput
  }

  export type BoardUncheckedCreateWithoutPlayerBoardsInput = {
    id?: string
    title: string
    description?: string | null
    startDate?: Date | string | null
    endDate?: Date | string | null
    size?: $Enums.BoardSize
    mode?: $Enums.BoardMode
    diceRollLimit?: number | null
    createdAt?: Date | string
    updatedAt?: Date | string
    authors?: BoardAuthorUncheckedCreateNestedManyWithoutBoardInput
    tiles?: TileUncheckedCreateNestedManyWithoutBoardInput
    boardTeams?: BoardTeamUncheckedCreateNestedManyWithoutBoardInput
  }

  export type BoardCreateOrConnectWithoutPlayerBoardsInput = {
    where: BoardWhereUniqueInput
    create: XOR<BoardCreateWithoutPlayerBoardsInput, BoardUncheckedCreateWithoutPlayerBoardsInput>
  }

  export type TeamCreateWithoutPlayerBoardsInput = {
    id?: string
    name: string
    iconUrl?: string | null
    createdAt?: Date | string
    updatedAt?: Date | string
    members?: TeamMemberCreateNestedManyWithoutTeamInput
    boardTeams?: BoardTeamCreateNestedManyWithoutTeamInput
  }

  export type TeamUncheckedCreateWithoutPlayerBoardsInput = {
    id?: string
    name: string
    iconUrl?: string | null
    createdAt?: Date | string
    updatedAt?: Date | string
    members?: TeamMemberUncheckedCreateNestedManyWithoutTeamInput
    boardTeams?: BoardTeamUncheckedCreateNestedManyWithoutTeamInput
  }

  export type TeamCreateOrConnectWithoutPlayerBoardsInput = {
    where: TeamWhereUniqueInput
    create: XOR<TeamCreateWithoutPlayerBoardsInput, TeamUncheckedCreateWithoutPlayerBoardsInput>
  }

  export type CompletedTileCreateWithoutPlayerBoardInput = {
    id?: string
    completedAt?: Date | string
    completedVia?: $Enums.CompletionSource
    tile: TileCreateNestedOneWithoutCompletedTilesInput
  }

  export type CompletedTileUncheckedCreateWithoutPlayerBoardInput = {
    id?: string
    tileId: string
    completedAt?: Date | string
    completedVia?: $Enums.CompletionSource
  }

  export type CompletedTileCreateOrConnectWithoutPlayerBoardInput = {
    where: CompletedTileWhereUniqueInput
    create: XOR<CompletedTileCreateWithoutPlayerBoardInput, CompletedTileUncheckedCreateWithoutPlayerBoardInput>
  }

  export type CompletedTileCreateManyPlayerBoardInputEnvelope = {
    data: CompletedTileCreateManyPlayerBoardInput | CompletedTileCreateManyPlayerBoardInput[]
    skipDuplicates?: boolean
  }

  export type UserUpsertWithoutPlayerBoardsInput = {
    update: XOR<UserUpdateWithoutPlayerBoardsInput, UserUncheckedUpdateWithoutPlayerBoardsInput>
    create: XOR<UserCreateWithoutPlayerBoardsInput, UserUncheckedCreateWithoutPlayerBoardsInput>
    where?: UserWhereInput
  }

  export type UserUpdateToOneWithWhereWithoutPlayerBoardsInput = {
    where?: UserWhereInput
    data: XOR<UserUpdateWithoutPlayerBoardsInput, UserUncheckedUpdateWithoutPlayerBoardsInput>
  }

  export type UserUpdateWithoutPlayerBoardsInput = {
    id?: StringFieldUpdateOperationsInput | string
    discordId?: StringFieldUpdateOperationsInput | string
    discordUsername?: StringFieldUpdateOperationsInput | string
    nickname?: NullableStringFieldUpdateOperationsInput | string | null
    avatarUrl?: NullableStringFieldUpdateOperationsInput | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    userRoles?: UserRoleUpdateManyWithoutUserNestedInput
    userPermissions?: UserPermissionUpdateManyWithoutUserNestedInput
    boardAuthors?: BoardAuthorUpdateManyWithoutUserNestedInput
    teamMembers?: TeamMemberUpdateManyWithoutUserNestedInput
  }

  export type UserUncheckedUpdateWithoutPlayerBoardsInput = {
    id?: StringFieldUpdateOperationsInput | string
    discordId?: StringFieldUpdateOperationsInput | string
    discordUsername?: StringFieldUpdateOperationsInput | string
    nickname?: NullableStringFieldUpdateOperationsInput | string | null
    avatarUrl?: NullableStringFieldUpdateOperationsInput | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    userRoles?: UserRoleUncheckedUpdateManyWithoutUserNestedInput
    userPermissions?: UserPermissionUncheckedUpdateManyWithoutUserNestedInput
    boardAuthors?: BoardAuthorUncheckedUpdateManyWithoutUserNestedInput
    teamMembers?: TeamMemberUncheckedUpdateManyWithoutUserNestedInput
  }

  export type BoardUpsertWithoutPlayerBoardsInput = {
    update: XOR<BoardUpdateWithoutPlayerBoardsInput, BoardUncheckedUpdateWithoutPlayerBoardsInput>
    create: XOR<BoardCreateWithoutPlayerBoardsInput, BoardUncheckedCreateWithoutPlayerBoardsInput>
    where?: BoardWhereInput
  }

  export type BoardUpdateToOneWithWhereWithoutPlayerBoardsInput = {
    where?: BoardWhereInput
    data: XOR<BoardUpdateWithoutPlayerBoardsInput, BoardUncheckedUpdateWithoutPlayerBoardsInput>
  }

  export type BoardUpdateWithoutPlayerBoardsInput = {
    id?: StringFieldUpdateOperationsInput | string
    title?: StringFieldUpdateOperationsInput | string
    description?: NullableStringFieldUpdateOperationsInput | string | null
    startDate?: NullableDateTimeFieldUpdateOperationsInput | Date | string | null
    endDate?: NullableDateTimeFieldUpdateOperationsInput | Date | string | null
    size?: EnumBoardSizeFieldUpdateOperationsInput | $Enums.BoardSize
    mode?: EnumBoardModeFieldUpdateOperationsInput | $Enums.BoardMode
    diceRollLimit?: NullableIntFieldUpdateOperationsInput | number | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    authors?: BoardAuthorUpdateManyWithoutBoardNestedInput
    tiles?: TileUpdateManyWithoutBoardNestedInput
    boardTeams?: BoardTeamUpdateManyWithoutBoardNestedInput
  }

  export type BoardUncheckedUpdateWithoutPlayerBoardsInput = {
    id?: StringFieldUpdateOperationsInput | string
    title?: StringFieldUpdateOperationsInput | string
    description?: NullableStringFieldUpdateOperationsInput | string | null
    startDate?: NullableDateTimeFieldUpdateOperationsInput | Date | string | null
    endDate?: NullableDateTimeFieldUpdateOperationsInput | Date | string | null
    size?: EnumBoardSizeFieldUpdateOperationsInput | $Enums.BoardSize
    mode?: EnumBoardModeFieldUpdateOperationsInput | $Enums.BoardMode
    diceRollLimit?: NullableIntFieldUpdateOperationsInput | number | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    authors?: BoardAuthorUncheckedUpdateManyWithoutBoardNestedInput
    tiles?: TileUncheckedUpdateManyWithoutBoardNestedInput
    boardTeams?: BoardTeamUncheckedUpdateManyWithoutBoardNestedInput
  }

  export type TeamUpsertWithoutPlayerBoardsInput = {
    update: XOR<TeamUpdateWithoutPlayerBoardsInput, TeamUncheckedUpdateWithoutPlayerBoardsInput>
    create: XOR<TeamCreateWithoutPlayerBoardsInput, TeamUncheckedCreateWithoutPlayerBoardsInput>
    where?: TeamWhereInput
  }

  export type TeamUpdateToOneWithWhereWithoutPlayerBoardsInput = {
    where?: TeamWhereInput
    data: XOR<TeamUpdateWithoutPlayerBoardsInput, TeamUncheckedUpdateWithoutPlayerBoardsInput>
  }

  export type TeamUpdateWithoutPlayerBoardsInput = {
    id?: StringFieldUpdateOperationsInput | string
    name?: StringFieldUpdateOperationsInput | string
    iconUrl?: NullableStringFieldUpdateOperationsInput | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    members?: TeamMemberUpdateManyWithoutTeamNestedInput
    boardTeams?: BoardTeamUpdateManyWithoutTeamNestedInput
  }

  export type TeamUncheckedUpdateWithoutPlayerBoardsInput = {
    id?: StringFieldUpdateOperationsInput | string
    name?: StringFieldUpdateOperationsInput | string
    iconUrl?: NullableStringFieldUpdateOperationsInput | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    members?: TeamMemberUncheckedUpdateManyWithoutTeamNestedInput
    boardTeams?: BoardTeamUncheckedUpdateManyWithoutTeamNestedInput
  }

  export type CompletedTileUpsertWithWhereUniqueWithoutPlayerBoardInput = {
    where: CompletedTileWhereUniqueInput
    update: XOR<CompletedTileUpdateWithoutPlayerBoardInput, CompletedTileUncheckedUpdateWithoutPlayerBoardInput>
    create: XOR<CompletedTileCreateWithoutPlayerBoardInput, CompletedTileUncheckedCreateWithoutPlayerBoardInput>
  }

  export type CompletedTileUpdateWithWhereUniqueWithoutPlayerBoardInput = {
    where: CompletedTileWhereUniqueInput
    data: XOR<CompletedTileUpdateWithoutPlayerBoardInput, CompletedTileUncheckedUpdateWithoutPlayerBoardInput>
  }

  export type CompletedTileUpdateManyWithWhereWithoutPlayerBoardInput = {
    where: CompletedTileScalarWhereInput
    data: XOR<CompletedTileUpdateManyMutationInput, CompletedTileUncheckedUpdateManyWithoutPlayerBoardInput>
  }

  export type PlayerBoardCreateWithoutCompletedTilesInput = {
    id?: string
    currentPosition?: number
    diceRollsToday?: number
    lastRollDate?: Date | string | null
    createdAt?: Date | string
    updatedAt?: Date | string
    user: UserCreateNestedOneWithoutPlayerBoardsInput
    board: BoardCreateNestedOneWithoutPlayerBoardsInput
    team?: TeamCreateNestedOneWithoutPlayerBoardsInput
  }

  export type PlayerBoardUncheckedCreateWithoutCompletedTilesInput = {
    id?: string
    userId: string
    boardId: string
    teamId?: string | null
    currentPosition?: number
    diceRollsToday?: number
    lastRollDate?: Date | string | null
    createdAt?: Date | string
    updatedAt?: Date | string
  }

  export type PlayerBoardCreateOrConnectWithoutCompletedTilesInput = {
    where: PlayerBoardWhereUniqueInput
    create: XOR<PlayerBoardCreateWithoutCompletedTilesInput, PlayerBoardUncheckedCreateWithoutCompletedTilesInput>
  }

  export type TileCreateWithoutCompletedTilesInput = {
    id?: string
    position: number
    titleOverride?: string | null
    type?: $Enums.TileType
    targetPosition?: number | null
    createdAt?: Date | string
    updatedAt?: Date | string
    board: BoardCreateNestedOneWithoutTilesInput
    task?: TaskCreateNestedOneWithoutTilesInput
  }

  export type TileUncheckedCreateWithoutCompletedTilesInput = {
    id?: string
    boardId: string
    position: number
    taskId?: string | null
    titleOverride?: string | null
    type?: $Enums.TileType
    targetPosition?: number | null
    createdAt?: Date | string
    updatedAt?: Date | string
  }

  export type TileCreateOrConnectWithoutCompletedTilesInput = {
    where: TileWhereUniqueInput
    create: XOR<TileCreateWithoutCompletedTilesInput, TileUncheckedCreateWithoutCompletedTilesInput>
  }

  export type PlayerBoardUpsertWithoutCompletedTilesInput = {
    update: XOR<PlayerBoardUpdateWithoutCompletedTilesInput, PlayerBoardUncheckedUpdateWithoutCompletedTilesInput>
    create: XOR<PlayerBoardCreateWithoutCompletedTilesInput, PlayerBoardUncheckedCreateWithoutCompletedTilesInput>
    where?: PlayerBoardWhereInput
  }

  export type PlayerBoardUpdateToOneWithWhereWithoutCompletedTilesInput = {
    where?: PlayerBoardWhereInput
    data: XOR<PlayerBoardUpdateWithoutCompletedTilesInput, PlayerBoardUncheckedUpdateWithoutCompletedTilesInput>
  }

  export type PlayerBoardUpdateWithoutCompletedTilesInput = {
    id?: StringFieldUpdateOperationsInput | string
    currentPosition?: IntFieldUpdateOperationsInput | number
    diceRollsToday?: IntFieldUpdateOperationsInput | number
    lastRollDate?: NullableDateTimeFieldUpdateOperationsInput | Date | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    user?: UserUpdateOneRequiredWithoutPlayerBoardsNestedInput
    board?: BoardUpdateOneRequiredWithoutPlayerBoardsNestedInput
    team?: TeamUpdateOneWithoutPlayerBoardsNestedInput
  }

  export type PlayerBoardUncheckedUpdateWithoutCompletedTilesInput = {
    id?: StringFieldUpdateOperationsInput | string
    userId?: StringFieldUpdateOperationsInput | string
    boardId?: StringFieldUpdateOperationsInput | string
    teamId?: NullableStringFieldUpdateOperationsInput | string | null
    currentPosition?: IntFieldUpdateOperationsInput | number
    diceRollsToday?: IntFieldUpdateOperationsInput | number
    lastRollDate?: NullableDateTimeFieldUpdateOperationsInput | Date | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type TileUpsertWithoutCompletedTilesInput = {
    update: XOR<TileUpdateWithoutCompletedTilesInput, TileUncheckedUpdateWithoutCompletedTilesInput>
    create: XOR<TileCreateWithoutCompletedTilesInput, TileUncheckedCreateWithoutCompletedTilesInput>
    where?: TileWhereInput
  }

  export type TileUpdateToOneWithWhereWithoutCompletedTilesInput = {
    where?: TileWhereInput
    data: XOR<TileUpdateWithoutCompletedTilesInput, TileUncheckedUpdateWithoutCompletedTilesInput>
  }

  export type TileUpdateWithoutCompletedTilesInput = {
    id?: StringFieldUpdateOperationsInput | string
    position?: IntFieldUpdateOperationsInput | number
    titleOverride?: NullableStringFieldUpdateOperationsInput | string | null
    type?: EnumTileTypeFieldUpdateOperationsInput | $Enums.TileType
    targetPosition?: NullableIntFieldUpdateOperationsInput | number | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    board?: BoardUpdateOneRequiredWithoutTilesNestedInput
    task?: TaskUpdateOneWithoutTilesNestedInput
  }

  export type TileUncheckedUpdateWithoutCompletedTilesInput = {
    id?: StringFieldUpdateOperationsInput | string
    boardId?: StringFieldUpdateOperationsInput | string
    position?: IntFieldUpdateOperationsInput | number
    taskId?: NullableStringFieldUpdateOperationsInput | string | null
    titleOverride?: NullableStringFieldUpdateOperationsInput | string | null
    type?: EnumTileTypeFieldUpdateOperationsInput | $Enums.TileType
    targetPosition?: NullableIntFieldUpdateOperationsInput | number | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type TeamMemberCreateWithoutTeamInput = {
    id?: string
    createdAt?: Date | string
    user: UserCreateNestedOneWithoutTeamMembersInput
  }

  export type TeamMemberUncheckedCreateWithoutTeamInput = {
    id?: string
    userId: string
    createdAt?: Date | string
  }

  export type TeamMemberCreateOrConnectWithoutTeamInput = {
    where: TeamMemberWhereUniqueInput
    create: XOR<TeamMemberCreateWithoutTeamInput, TeamMemberUncheckedCreateWithoutTeamInput>
  }

  export type TeamMemberCreateManyTeamInputEnvelope = {
    data: TeamMemberCreateManyTeamInput | TeamMemberCreateManyTeamInput[]
    skipDuplicates?: boolean
  }

  export type BoardTeamCreateWithoutTeamInput = {
    id?: string
    createdAt?: Date | string
    board: BoardCreateNestedOneWithoutBoardTeamsInput
  }

  export type BoardTeamUncheckedCreateWithoutTeamInput = {
    id?: string
    boardId: string
    createdAt?: Date | string
  }

  export type BoardTeamCreateOrConnectWithoutTeamInput = {
    where: BoardTeamWhereUniqueInput
    create: XOR<BoardTeamCreateWithoutTeamInput, BoardTeamUncheckedCreateWithoutTeamInput>
  }

  export type BoardTeamCreateManyTeamInputEnvelope = {
    data: BoardTeamCreateManyTeamInput | BoardTeamCreateManyTeamInput[]
    skipDuplicates?: boolean
  }

  export type PlayerBoardCreateWithoutTeamInput = {
    id?: string
    currentPosition?: number
    diceRollsToday?: number
    lastRollDate?: Date | string | null
    createdAt?: Date | string
    updatedAt?: Date | string
    user: UserCreateNestedOneWithoutPlayerBoardsInput
    board: BoardCreateNestedOneWithoutPlayerBoardsInput
    completedTiles?: CompletedTileCreateNestedManyWithoutPlayerBoardInput
  }

  export type PlayerBoardUncheckedCreateWithoutTeamInput = {
    id?: string
    userId: string
    boardId: string
    currentPosition?: number
    diceRollsToday?: number
    lastRollDate?: Date | string | null
    createdAt?: Date | string
    updatedAt?: Date | string
    completedTiles?: CompletedTileUncheckedCreateNestedManyWithoutPlayerBoardInput
  }

  export type PlayerBoardCreateOrConnectWithoutTeamInput = {
    where: PlayerBoardWhereUniqueInput
    create: XOR<PlayerBoardCreateWithoutTeamInput, PlayerBoardUncheckedCreateWithoutTeamInput>
  }

  export type PlayerBoardCreateManyTeamInputEnvelope = {
    data: PlayerBoardCreateManyTeamInput | PlayerBoardCreateManyTeamInput[]
    skipDuplicates?: boolean
  }

  export type TeamMemberUpsertWithWhereUniqueWithoutTeamInput = {
    where: TeamMemberWhereUniqueInput
    update: XOR<TeamMemberUpdateWithoutTeamInput, TeamMemberUncheckedUpdateWithoutTeamInput>
    create: XOR<TeamMemberCreateWithoutTeamInput, TeamMemberUncheckedCreateWithoutTeamInput>
  }

  export type TeamMemberUpdateWithWhereUniqueWithoutTeamInput = {
    where: TeamMemberWhereUniqueInput
    data: XOR<TeamMemberUpdateWithoutTeamInput, TeamMemberUncheckedUpdateWithoutTeamInput>
  }

  export type TeamMemberUpdateManyWithWhereWithoutTeamInput = {
    where: TeamMemberScalarWhereInput
    data: XOR<TeamMemberUpdateManyMutationInput, TeamMemberUncheckedUpdateManyWithoutTeamInput>
  }

  export type BoardTeamUpsertWithWhereUniqueWithoutTeamInput = {
    where: BoardTeamWhereUniqueInput
    update: XOR<BoardTeamUpdateWithoutTeamInput, BoardTeamUncheckedUpdateWithoutTeamInput>
    create: XOR<BoardTeamCreateWithoutTeamInput, BoardTeamUncheckedCreateWithoutTeamInput>
  }

  export type BoardTeamUpdateWithWhereUniqueWithoutTeamInput = {
    where: BoardTeamWhereUniqueInput
    data: XOR<BoardTeamUpdateWithoutTeamInput, BoardTeamUncheckedUpdateWithoutTeamInput>
  }

  export type BoardTeamUpdateManyWithWhereWithoutTeamInput = {
    where: BoardTeamScalarWhereInput
    data: XOR<BoardTeamUpdateManyMutationInput, BoardTeamUncheckedUpdateManyWithoutTeamInput>
  }

  export type PlayerBoardUpsertWithWhereUniqueWithoutTeamInput = {
    where: PlayerBoardWhereUniqueInput
    update: XOR<PlayerBoardUpdateWithoutTeamInput, PlayerBoardUncheckedUpdateWithoutTeamInput>
    create: XOR<PlayerBoardCreateWithoutTeamInput, PlayerBoardUncheckedCreateWithoutTeamInput>
  }

  export type PlayerBoardUpdateWithWhereUniqueWithoutTeamInput = {
    where: PlayerBoardWhereUniqueInput
    data: XOR<PlayerBoardUpdateWithoutTeamInput, PlayerBoardUncheckedUpdateWithoutTeamInput>
  }

  export type PlayerBoardUpdateManyWithWhereWithoutTeamInput = {
    where: PlayerBoardScalarWhereInput
    data: XOR<PlayerBoardUpdateManyMutationInput, PlayerBoardUncheckedUpdateManyWithoutTeamInput>
  }

  export type TeamCreateWithoutMembersInput = {
    id?: string
    name: string
    iconUrl?: string | null
    createdAt?: Date | string
    updatedAt?: Date | string
    boardTeams?: BoardTeamCreateNestedManyWithoutTeamInput
    playerBoards?: PlayerBoardCreateNestedManyWithoutTeamInput
  }

  export type TeamUncheckedCreateWithoutMembersInput = {
    id?: string
    name: string
    iconUrl?: string | null
    createdAt?: Date | string
    updatedAt?: Date | string
    boardTeams?: BoardTeamUncheckedCreateNestedManyWithoutTeamInput
    playerBoards?: PlayerBoardUncheckedCreateNestedManyWithoutTeamInput
  }

  export type TeamCreateOrConnectWithoutMembersInput = {
    where: TeamWhereUniqueInput
    create: XOR<TeamCreateWithoutMembersInput, TeamUncheckedCreateWithoutMembersInput>
  }

  export type UserCreateWithoutTeamMembersInput = {
    id?: string
    discordId: string
    discordUsername: string
    nickname?: string | null
    avatarUrl?: string | null
    createdAt?: Date | string
    updatedAt?: Date | string
    userRoles?: UserRoleCreateNestedManyWithoutUserInput
    userPermissions?: UserPermissionCreateNestedManyWithoutUserInput
    boardAuthors?: BoardAuthorCreateNestedManyWithoutUserInput
    playerBoards?: PlayerBoardCreateNestedManyWithoutUserInput
  }

  export type UserUncheckedCreateWithoutTeamMembersInput = {
    id?: string
    discordId: string
    discordUsername: string
    nickname?: string | null
    avatarUrl?: string | null
    createdAt?: Date | string
    updatedAt?: Date | string
    userRoles?: UserRoleUncheckedCreateNestedManyWithoutUserInput
    userPermissions?: UserPermissionUncheckedCreateNestedManyWithoutUserInput
    boardAuthors?: BoardAuthorUncheckedCreateNestedManyWithoutUserInput
    playerBoards?: PlayerBoardUncheckedCreateNestedManyWithoutUserInput
  }

  export type UserCreateOrConnectWithoutTeamMembersInput = {
    where: UserWhereUniqueInput
    create: XOR<UserCreateWithoutTeamMembersInput, UserUncheckedCreateWithoutTeamMembersInput>
  }

  export type TeamUpsertWithoutMembersInput = {
    update: XOR<TeamUpdateWithoutMembersInput, TeamUncheckedUpdateWithoutMembersInput>
    create: XOR<TeamCreateWithoutMembersInput, TeamUncheckedCreateWithoutMembersInput>
    where?: TeamWhereInput
  }

  export type TeamUpdateToOneWithWhereWithoutMembersInput = {
    where?: TeamWhereInput
    data: XOR<TeamUpdateWithoutMembersInput, TeamUncheckedUpdateWithoutMembersInput>
  }

  export type TeamUpdateWithoutMembersInput = {
    id?: StringFieldUpdateOperationsInput | string
    name?: StringFieldUpdateOperationsInput | string
    iconUrl?: NullableStringFieldUpdateOperationsInput | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    boardTeams?: BoardTeamUpdateManyWithoutTeamNestedInput
    playerBoards?: PlayerBoardUpdateManyWithoutTeamNestedInput
  }

  export type TeamUncheckedUpdateWithoutMembersInput = {
    id?: StringFieldUpdateOperationsInput | string
    name?: StringFieldUpdateOperationsInput | string
    iconUrl?: NullableStringFieldUpdateOperationsInput | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    boardTeams?: BoardTeamUncheckedUpdateManyWithoutTeamNestedInput
    playerBoards?: PlayerBoardUncheckedUpdateManyWithoutTeamNestedInput
  }

  export type UserUpsertWithoutTeamMembersInput = {
    update: XOR<UserUpdateWithoutTeamMembersInput, UserUncheckedUpdateWithoutTeamMembersInput>
    create: XOR<UserCreateWithoutTeamMembersInput, UserUncheckedCreateWithoutTeamMembersInput>
    where?: UserWhereInput
  }

  export type UserUpdateToOneWithWhereWithoutTeamMembersInput = {
    where?: UserWhereInput
    data: XOR<UserUpdateWithoutTeamMembersInput, UserUncheckedUpdateWithoutTeamMembersInput>
  }

  export type UserUpdateWithoutTeamMembersInput = {
    id?: StringFieldUpdateOperationsInput | string
    discordId?: StringFieldUpdateOperationsInput | string
    discordUsername?: StringFieldUpdateOperationsInput | string
    nickname?: NullableStringFieldUpdateOperationsInput | string | null
    avatarUrl?: NullableStringFieldUpdateOperationsInput | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    userRoles?: UserRoleUpdateManyWithoutUserNestedInput
    userPermissions?: UserPermissionUpdateManyWithoutUserNestedInput
    boardAuthors?: BoardAuthorUpdateManyWithoutUserNestedInput
    playerBoards?: PlayerBoardUpdateManyWithoutUserNestedInput
  }

  export type UserUncheckedUpdateWithoutTeamMembersInput = {
    id?: StringFieldUpdateOperationsInput | string
    discordId?: StringFieldUpdateOperationsInput | string
    discordUsername?: StringFieldUpdateOperationsInput | string
    nickname?: NullableStringFieldUpdateOperationsInput | string | null
    avatarUrl?: NullableStringFieldUpdateOperationsInput | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    userRoles?: UserRoleUncheckedUpdateManyWithoutUserNestedInput
    userPermissions?: UserPermissionUncheckedUpdateManyWithoutUserNestedInput
    boardAuthors?: BoardAuthorUncheckedUpdateManyWithoutUserNestedInput
    playerBoards?: PlayerBoardUncheckedUpdateManyWithoutUserNestedInput
  }

  export type UserRoleCreateManyRoleInput = {
    id?: string
    userId: string
    createdAt?: Date | string
  }

  export type UserRoleUpdateWithoutRoleInput = {
    id?: StringFieldUpdateOperationsInput | string
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    user?: UserUpdateOneRequiredWithoutUserRolesNestedInput
  }

  export type UserRoleUncheckedUpdateWithoutRoleInput = {
    id?: StringFieldUpdateOperationsInput | string
    userId?: StringFieldUpdateOperationsInput | string
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type UserRoleUncheckedUpdateManyWithoutRoleInput = {
    id?: StringFieldUpdateOperationsInput | string
    userId?: StringFieldUpdateOperationsInput | string
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type UserRoleCreateManyUserInput = {
    id?: string
    roleId: string
    createdAt?: Date | string
  }

  export type UserPermissionCreateManyUserInput = {
    id?: string
    permissionKey: string
    createdAt?: Date | string
  }

  export type BoardAuthorCreateManyUserInput = {
    id?: string
    boardId: string
    isOwner?: boolean
    createdAt?: Date | string
  }

  export type PlayerBoardCreateManyUserInput = {
    id?: string
    boardId: string
    teamId?: string | null
    currentPosition?: number
    diceRollsToday?: number
    lastRollDate?: Date | string | null
    createdAt?: Date | string
    updatedAt?: Date | string
  }

  export type TeamMemberCreateManyUserInput = {
    id?: string
    teamId: string
    createdAt?: Date | string
  }

  export type UserRoleUpdateWithoutUserInput = {
    id?: StringFieldUpdateOperationsInput | string
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    role?: RoleUpdateOneRequiredWithoutUserRolesNestedInput
  }

  export type UserRoleUncheckedUpdateWithoutUserInput = {
    id?: StringFieldUpdateOperationsInput | string
    roleId?: StringFieldUpdateOperationsInput | string
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type UserRoleUncheckedUpdateManyWithoutUserInput = {
    id?: StringFieldUpdateOperationsInput | string
    roleId?: StringFieldUpdateOperationsInput | string
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type UserPermissionUpdateWithoutUserInput = {
    id?: StringFieldUpdateOperationsInput | string
    permissionKey?: StringFieldUpdateOperationsInput | string
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type UserPermissionUncheckedUpdateWithoutUserInput = {
    id?: StringFieldUpdateOperationsInput | string
    permissionKey?: StringFieldUpdateOperationsInput | string
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type UserPermissionUncheckedUpdateManyWithoutUserInput = {
    id?: StringFieldUpdateOperationsInput | string
    permissionKey?: StringFieldUpdateOperationsInput | string
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type BoardAuthorUpdateWithoutUserInput = {
    id?: StringFieldUpdateOperationsInput | string
    isOwner?: BoolFieldUpdateOperationsInput | boolean
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    board?: BoardUpdateOneRequiredWithoutAuthorsNestedInput
  }

  export type BoardAuthorUncheckedUpdateWithoutUserInput = {
    id?: StringFieldUpdateOperationsInput | string
    boardId?: StringFieldUpdateOperationsInput | string
    isOwner?: BoolFieldUpdateOperationsInput | boolean
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type BoardAuthorUncheckedUpdateManyWithoutUserInput = {
    id?: StringFieldUpdateOperationsInput | string
    boardId?: StringFieldUpdateOperationsInput | string
    isOwner?: BoolFieldUpdateOperationsInput | boolean
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type PlayerBoardUpdateWithoutUserInput = {
    id?: StringFieldUpdateOperationsInput | string
    currentPosition?: IntFieldUpdateOperationsInput | number
    diceRollsToday?: IntFieldUpdateOperationsInput | number
    lastRollDate?: NullableDateTimeFieldUpdateOperationsInput | Date | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    board?: BoardUpdateOneRequiredWithoutPlayerBoardsNestedInput
    team?: TeamUpdateOneWithoutPlayerBoardsNestedInput
    completedTiles?: CompletedTileUpdateManyWithoutPlayerBoardNestedInput
  }

  export type PlayerBoardUncheckedUpdateWithoutUserInput = {
    id?: StringFieldUpdateOperationsInput | string
    boardId?: StringFieldUpdateOperationsInput | string
    teamId?: NullableStringFieldUpdateOperationsInput | string | null
    currentPosition?: IntFieldUpdateOperationsInput | number
    diceRollsToday?: IntFieldUpdateOperationsInput | number
    lastRollDate?: NullableDateTimeFieldUpdateOperationsInput | Date | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    completedTiles?: CompletedTileUncheckedUpdateManyWithoutPlayerBoardNestedInput
  }

  export type PlayerBoardUncheckedUpdateManyWithoutUserInput = {
    id?: StringFieldUpdateOperationsInput | string
    boardId?: StringFieldUpdateOperationsInput | string
    teamId?: NullableStringFieldUpdateOperationsInput | string | null
    currentPosition?: IntFieldUpdateOperationsInput | number
    diceRollsToday?: IntFieldUpdateOperationsInput | number
    lastRollDate?: NullableDateTimeFieldUpdateOperationsInput | Date | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type TeamMemberUpdateWithoutUserInput = {
    id?: StringFieldUpdateOperationsInput | string
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    team?: TeamUpdateOneRequiredWithoutMembersNestedInput
  }

  export type TeamMemberUncheckedUpdateWithoutUserInput = {
    id?: StringFieldUpdateOperationsInput | string
    teamId?: StringFieldUpdateOperationsInput | string
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type TeamMemberUncheckedUpdateManyWithoutUserInput = {
    id?: StringFieldUpdateOperationsInput | string
    teamId?: StringFieldUpdateOperationsInput | string
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type TileCreateManyTaskInput = {
    id?: string
    boardId: string
    position: number
    titleOverride?: string | null
    type?: $Enums.TileType
    targetPosition?: number | null
    createdAt?: Date | string
    updatedAt?: Date | string
  }

  export type TileUpdateWithoutTaskInput = {
    id?: StringFieldUpdateOperationsInput | string
    position?: IntFieldUpdateOperationsInput | number
    titleOverride?: NullableStringFieldUpdateOperationsInput | string | null
    type?: EnumTileTypeFieldUpdateOperationsInput | $Enums.TileType
    targetPosition?: NullableIntFieldUpdateOperationsInput | number | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    board?: BoardUpdateOneRequiredWithoutTilesNestedInput
    completedTiles?: CompletedTileUpdateManyWithoutTileNestedInput
  }

  export type TileUncheckedUpdateWithoutTaskInput = {
    id?: StringFieldUpdateOperationsInput | string
    boardId?: StringFieldUpdateOperationsInput | string
    position?: IntFieldUpdateOperationsInput | number
    titleOverride?: NullableStringFieldUpdateOperationsInput | string | null
    type?: EnumTileTypeFieldUpdateOperationsInput | $Enums.TileType
    targetPosition?: NullableIntFieldUpdateOperationsInput | number | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    completedTiles?: CompletedTileUncheckedUpdateManyWithoutTileNestedInput
  }

  export type TileUncheckedUpdateManyWithoutTaskInput = {
    id?: StringFieldUpdateOperationsInput | string
    boardId?: StringFieldUpdateOperationsInput | string
    position?: IntFieldUpdateOperationsInput | number
    titleOverride?: NullableStringFieldUpdateOperationsInput | string | null
    type?: EnumTileTypeFieldUpdateOperationsInput | $Enums.TileType
    targetPosition?: NullableIntFieldUpdateOperationsInput | number | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type BoardAuthorCreateManyBoardInput = {
    id?: string
    userId: string
    isOwner?: boolean
    createdAt?: Date | string
  }

  export type TileCreateManyBoardInput = {
    id?: string
    position: number
    taskId?: string | null
    titleOverride?: string | null
    type?: $Enums.TileType
    targetPosition?: number | null
    createdAt?: Date | string
    updatedAt?: Date | string
  }

  export type PlayerBoardCreateManyBoardInput = {
    id?: string
    userId: string
    teamId?: string | null
    currentPosition?: number
    diceRollsToday?: number
    lastRollDate?: Date | string | null
    createdAt?: Date | string
    updatedAt?: Date | string
  }

  export type BoardTeamCreateManyBoardInput = {
    id?: string
    teamId: string
    createdAt?: Date | string
  }

  export type BoardAuthorUpdateWithoutBoardInput = {
    id?: StringFieldUpdateOperationsInput | string
    isOwner?: BoolFieldUpdateOperationsInput | boolean
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    user?: UserUpdateOneRequiredWithoutBoardAuthorsNestedInput
  }

  export type BoardAuthorUncheckedUpdateWithoutBoardInput = {
    id?: StringFieldUpdateOperationsInput | string
    userId?: StringFieldUpdateOperationsInput | string
    isOwner?: BoolFieldUpdateOperationsInput | boolean
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type BoardAuthorUncheckedUpdateManyWithoutBoardInput = {
    id?: StringFieldUpdateOperationsInput | string
    userId?: StringFieldUpdateOperationsInput | string
    isOwner?: BoolFieldUpdateOperationsInput | boolean
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type TileUpdateWithoutBoardInput = {
    id?: StringFieldUpdateOperationsInput | string
    position?: IntFieldUpdateOperationsInput | number
    titleOverride?: NullableStringFieldUpdateOperationsInput | string | null
    type?: EnumTileTypeFieldUpdateOperationsInput | $Enums.TileType
    targetPosition?: NullableIntFieldUpdateOperationsInput | number | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    task?: TaskUpdateOneWithoutTilesNestedInput
    completedTiles?: CompletedTileUpdateManyWithoutTileNestedInput
  }

  export type TileUncheckedUpdateWithoutBoardInput = {
    id?: StringFieldUpdateOperationsInput | string
    position?: IntFieldUpdateOperationsInput | number
    taskId?: NullableStringFieldUpdateOperationsInput | string | null
    titleOverride?: NullableStringFieldUpdateOperationsInput | string | null
    type?: EnumTileTypeFieldUpdateOperationsInput | $Enums.TileType
    targetPosition?: NullableIntFieldUpdateOperationsInput | number | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    completedTiles?: CompletedTileUncheckedUpdateManyWithoutTileNestedInput
  }

  export type TileUncheckedUpdateManyWithoutBoardInput = {
    id?: StringFieldUpdateOperationsInput | string
    position?: IntFieldUpdateOperationsInput | number
    taskId?: NullableStringFieldUpdateOperationsInput | string | null
    titleOverride?: NullableStringFieldUpdateOperationsInput | string | null
    type?: EnumTileTypeFieldUpdateOperationsInput | $Enums.TileType
    targetPosition?: NullableIntFieldUpdateOperationsInput | number | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type PlayerBoardUpdateWithoutBoardInput = {
    id?: StringFieldUpdateOperationsInput | string
    currentPosition?: IntFieldUpdateOperationsInput | number
    diceRollsToday?: IntFieldUpdateOperationsInput | number
    lastRollDate?: NullableDateTimeFieldUpdateOperationsInput | Date | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    user?: UserUpdateOneRequiredWithoutPlayerBoardsNestedInput
    team?: TeamUpdateOneWithoutPlayerBoardsNestedInput
    completedTiles?: CompletedTileUpdateManyWithoutPlayerBoardNestedInput
  }

  export type PlayerBoardUncheckedUpdateWithoutBoardInput = {
    id?: StringFieldUpdateOperationsInput | string
    userId?: StringFieldUpdateOperationsInput | string
    teamId?: NullableStringFieldUpdateOperationsInput | string | null
    currentPosition?: IntFieldUpdateOperationsInput | number
    diceRollsToday?: IntFieldUpdateOperationsInput | number
    lastRollDate?: NullableDateTimeFieldUpdateOperationsInput | Date | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    completedTiles?: CompletedTileUncheckedUpdateManyWithoutPlayerBoardNestedInput
  }

  export type PlayerBoardUncheckedUpdateManyWithoutBoardInput = {
    id?: StringFieldUpdateOperationsInput | string
    userId?: StringFieldUpdateOperationsInput | string
    teamId?: NullableStringFieldUpdateOperationsInput | string | null
    currentPosition?: IntFieldUpdateOperationsInput | number
    diceRollsToday?: IntFieldUpdateOperationsInput | number
    lastRollDate?: NullableDateTimeFieldUpdateOperationsInput | Date | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type BoardTeamUpdateWithoutBoardInput = {
    id?: StringFieldUpdateOperationsInput | string
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    team?: TeamUpdateOneRequiredWithoutBoardTeamsNestedInput
  }

  export type BoardTeamUncheckedUpdateWithoutBoardInput = {
    id?: StringFieldUpdateOperationsInput | string
    teamId?: StringFieldUpdateOperationsInput | string
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type BoardTeamUncheckedUpdateManyWithoutBoardInput = {
    id?: StringFieldUpdateOperationsInput | string
    teamId?: StringFieldUpdateOperationsInput | string
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type CompletedTileCreateManyTileInput = {
    id?: string
    playerBoardId: string
    completedAt?: Date | string
    completedVia?: $Enums.CompletionSource
  }

  export type CompletedTileUpdateWithoutTileInput = {
    id?: StringFieldUpdateOperationsInput | string
    completedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    completedVia?: EnumCompletionSourceFieldUpdateOperationsInput | $Enums.CompletionSource
    playerBoard?: PlayerBoardUpdateOneRequiredWithoutCompletedTilesNestedInput
  }

  export type CompletedTileUncheckedUpdateWithoutTileInput = {
    id?: StringFieldUpdateOperationsInput | string
    playerBoardId?: StringFieldUpdateOperationsInput | string
    completedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    completedVia?: EnumCompletionSourceFieldUpdateOperationsInput | $Enums.CompletionSource
  }

  export type CompletedTileUncheckedUpdateManyWithoutTileInput = {
    id?: StringFieldUpdateOperationsInput | string
    playerBoardId?: StringFieldUpdateOperationsInput | string
    completedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    completedVia?: EnumCompletionSourceFieldUpdateOperationsInput | $Enums.CompletionSource
  }

  export type CompletedTileCreateManyPlayerBoardInput = {
    id?: string
    tileId: string
    completedAt?: Date | string
    completedVia?: $Enums.CompletionSource
  }

  export type CompletedTileUpdateWithoutPlayerBoardInput = {
    id?: StringFieldUpdateOperationsInput | string
    completedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    completedVia?: EnumCompletionSourceFieldUpdateOperationsInput | $Enums.CompletionSource
    tile?: TileUpdateOneRequiredWithoutCompletedTilesNestedInput
  }

  export type CompletedTileUncheckedUpdateWithoutPlayerBoardInput = {
    id?: StringFieldUpdateOperationsInput | string
    tileId?: StringFieldUpdateOperationsInput | string
    completedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    completedVia?: EnumCompletionSourceFieldUpdateOperationsInput | $Enums.CompletionSource
  }

  export type CompletedTileUncheckedUpdateManyWithoutPlayerBoardInput = {
    id?: StringFieldUpdateOperationsInput | string
    tileId?: StringFieldUpdateOperationsInput | string
    completedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    completedVia?: EnumCompletionSourceFieldUpdateOperationsInput | $Enums.CompletionSource
  }

  export type TeamMemberCreateManyTeamInput = {
    id?: string
    userId: string
    createdAt?: Date | string
  }

  export type BoardTeamCreateManyTeamInput = {
    id?: string
    boardId: string
    createdAt?: Date | string
  }

  export type PlayerBoardCreateManyTeamInput = {
    id?: string
    userId: string
    boardId: string
    currentPosition?: number
    diceRollsToday?: number
    lastRollDate?: Date | string | null
    createdAt?: Date | string
    updatedAt?: Date | string
  }

  export type TeamMemberUpdateWithoutTeamInput = {
    id?: StringFieldUpdateOperationsInput | string
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    user?: UserUpdateOneRequiredWithoutTeamMembersNestedInput
  }

  export type TeamMemberUncheckedUpdateWithoutTeamInput = {
    id?: StringFieldUpdateOperationsInput | string
    userId?: StringFieldUpdateOperationsInput | string
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type TeamMemberUncheckedUpdateManyWithoutTeamInput = {
    id?: StringFieldUpdateOperationsInput | string
    userId?: StringFieldUpdateOperationsInput | string
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type BoardTeamUpdateWithoutTeamInput = {
    id?: StringFieldUpdateOperationsInput | string
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    board?: BoardUpdateOneRequiredWithoutBoardTeamsNestedInput
  }

  export type BoardTeamUncheckedUpdateWithoutTeamInput = {
    id?: StringFieldUpdateOperationsInput | string
    boardId?: StringFieldUpdateOperationsInput | string
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type BoardTeamUncheckedUpdateManyWithoutTeamInput = {
    id?: StringFieldUpdateOperationsInput | string
    boardId?: StringFieldUpdateOperationsInput | string
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }

  export type PlayerBoardUpdateWithoutTeamInput = {
    id?: StringFieldUpdateOperationsInput | string
    currentPosition?: IntFieldUpdateOperationsInput | number
    diceRollsToday?: IntFieldUpdateOperationsInput | number
    lastRollDate?: NullableDateTimeFieldUpdateOperationsInput | Date | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    user?: UserUpdateOneRequiredWithoutPlayerBoardsNestedInput
    board?: BoardUpdateOneRequiredWithoutPlayerBoardsNestedInput
    completedTiles?: CompletedTileUpdateManyWithoutPlayerBoardNestedInput
  }

  export type PlayerBoardUncheckedUpdateWithoutTeamInput = {
    id?: StringFieldUpdateOperationsInput | string
    userId?: StringFieldUpdateOperationsInput | string
    boardId?: StringFieldUpdateOperationsInput | string
    currentPosition?: IntFieldUpdateOperationsInput | number
    diceRollsToday?: IntFieldUpdateOperationsInput | number
    lastRollDate?: NullableDateTimeFieldUpdateOperationsInput | Date | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
    completedTiles?: CompletedTileUncheckedUpdateManyWithoutPlayerBoardNestedInput
  }

  export type PlayerBoardUncheckedUpdateManyWithoutTeamInput = {
    id?: StringFieldUpdateOperationsInput | string
    userId?: StringFieldUpdateOperationsInput | string
    boardId?: StringFieldUpdateOperationsInput | string
    currentPosition?: IntFieldUpdateOperationsInput | number
    diceRollsToday?: IntFieldUpdateOperationsInput | number
    lastRollDate?: NullableDateTimeFieldUpdateOperationsInput | Date | string | null
    createdAt?: DateTimeFieldUpdateOperationsInput | Date | string
    updatedAt?: DateTimeFieldUpdateOperationsInput | Date | string
  }



  /**
   * Batch Payload for updateMany & deleteMany & createMany
   */

  export type BatchPayload = {
    count: number
  }

  /**
   * DMMF
   */
  export const dmmf: runtime.BaseDMMF
}