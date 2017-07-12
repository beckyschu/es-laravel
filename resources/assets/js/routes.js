export const routes = [
    {
        path: '/',
        component: require('./layouts/default'),
        redirect: '/dashboard/summary',
        children: [
            {
                path: 'dashboard',
                component: require('./components/dashboard'),
                redirect: '/dashboard/summary',
                children: [
                    {
                        path: 'summary',
                        component: require('./components/dashboard/summary')
                    },
                    {
                        path: 'activity',
                        component: require('./components/dashboard/events')
                    },
                ]
            },
            {
                path: 'browser',
                component: require('./components/browser'),
                redirect: '/browser/discoveries',
                children: [
                    {
                        path: 'discoveries',
                        component: require('./components/browser/discoveries/listing')
                    },
                    {
                        path: 'discoveries/:id',
                        component: require('./components/browser/discoveries/show')
                    },
                    {
                        path: 'sellers',
                        component: require('./components/browser/sellers/listing')
                    },
                    {
                        path: 'sellers/:id',
                        component: require('./components/browser/sellers/show')
                    }
                ]
            },
            {
                path: 'reports',
                component: require('./components/reports'),
                redirect: '/reports/summary',
                needs: 'reports.read',
                children: [
                    {
                        path: 'summary',
                        component: require('./components/reports/summary')
                    },
                    {
                        path: 'log',
                        component: require('./components/reports/log')
                    },
                    {
                        name: 'showReport',
                        path: 'log/:id',
                        component: require('./components/reports/generator')
                    },
                    {
                        name: 'generateReport',
                        path: 'generate',
                        component: require('./components/reports/generator')
                    }
                ]
            },
            {
                path: 'admin',
                component: require('./components/admin'),
                redirect: '/admin/accounts',
                children: [
                    {
                        path: 'accounts',
                        component: require('./components/admin/accounts/listing')
                    },
                    {
                        path: 'accounts/:id',
                        component: require('./components/admin/accounts/show')
                    },
                    {
                        path: 'assets',
                        component: require('./components/admin/assets/listing')
                    },
                    {
                        path: 'assets/:id',
                        component: require('./components/admin/assets/show')
                    },
                    {
                        path: 'assets/:asset/keywords/:id',
                        component: require('./components/admin/keywords/show')
                    },
                    {
                        path: 'users',
                        component: require('./components/admin/users/listing')
                    },
                    {
                        path: 'users/:id',
                        component: require('./components/admin/users/show')
                    },
                    {
                        path: 'crawlers',
                        component: require('./components/admin/crawlers/overview')
                    },
                    {
                        path: 'crawlers/:id',
                        component: require('./components/admin/crawlers/show')
                    },
                    {
                        path: 'crawlers/:crawler/:id',
                        component: require('./components/admin/crawlers/crawl')
                    },
                    {
                        path: 'rules',
                        component: require('./components/admin/rules/listing')
                    },
                    {
                        path: 'rules/:id',
                        component: require('./components/admin/rules/show')
                    },
                ]
            },
            {
                path: 'me',
                component: require('./components/me'),
                redirect: '/me/account',
                children: [
                    {
                        path: 'account',
                        component: require('./components/me/account')
                    },
                    {
                        path: 'activity',
                        component: require('./components/me/events')
                    }
                ]
            },
        ]
    },
    {
        path: '/login',
        component: require('./components/login'),
        meta: {
            allowsGuest: true
        }
    }
]
