/**
 * The seats of the multi-user walkthrough, one account each (see E2eSeeder).
 * Each is the one before it plus one thing, so a gap between two of them is
 * the finding.
 */
export const SEATS = {
    member: { username: 'e2e_member', sees: 'reading, joining, own settings' },
    creator: { username: 'e2e_creator', sees: 'plus creating events' },
    cohost: { username: 'e2e_cohost', sees: 'plus running somebody else\'s event' },
    owner: { username: 'e2e_owner', sees: 'plus destroying it' },
    admin: { username: 'e2e_admin', sees: 'everything, through /admin' },
};

/** Not rungs of the ladder: accounts in a particular state of setup. */
export const STATES = {
    newcomer: { username: 'e2e_newcomer' },
    emailer: { email: 'emailer@e2e.test' },
    unreachable: { username: 'e2e_unreachable' },
    settler: { username: 'e2e_settler' },
    leaver: { username: 'e2e_leaver' },
    promotee: { username: 'e2e_promotee' },
    changer: { username: 'e2e_changer' },
    notifier: { username: 'e2e_notifier' },
    roled: { username: 'e2e_roled' },
    stranded: { username: 'e2e_stranded' },
};

export const PASSWORD = 'E2e-Password-1';

/** The four widths of the pass. The band between 768 and 1280 is where layout bugs live. */
export const WIDTHS = [1280, 1024, 768, 375];
