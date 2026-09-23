let nextKey = 0;

/** One row for OsrsCharactersField; the key survives reordering. */
export function characterRow(username = '') {
    return { key: `c${nextKey++}`, username };
}

/** Rows for a stored list of names — one empty row when there are none. */
export function characterRows(names) {
    return (names ?? []).length ? names.map(characterRow) : [characterRow()];
}

/** What the server takes: the typed names, blanks dropped, order kept. */
export function characterNames(rows) {
    return rows.map((row) => row.username.trim()).filter((name) => name !== '');
}
