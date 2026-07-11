/** Auth routes must use `<a href>` from marketing (full page load) — not Inertia `<Link>`. */
export const MARKETING_ROUTES = {
    login: '/login',
    register: '/register',
} as const;
