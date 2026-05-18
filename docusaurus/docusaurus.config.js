const config = {
  title: 'Magento Laravel Modernization',
  tagline: 'User and developer documentation for the Magento CE 1.9.4.5 to Laravel modernization.',
  favicon: 'img/favicon.ico',
  url: 'http://127.0.0.1:3010',
  baseUrl: '/',
  organizationName: 'fabianwesner',
  projectName: 'magento-laravel',
  onBrokenLinks: 'throw',
  markdown: {
    hooks: {
      onBrokenMarkdownLinks: 'warn',
    },
  },
  i18n: {
    defaultLocale: 'en',
    locales: ['en'],
  },
  presets: [
    [
      'classic',
      {
        docs: {
          sidebarPath: './sidebars.js',
          routeBasePath: '/',
        },
        blog: false,
        theme: {
          customCss: './src/css/custom.css',
        },
      },
    ],
  ],
  themeConfig: {
    navbar: {
      title: 'Magento Laravel',
      items: [
        {
          type: 'docSidebar',
          sidebarId: 'userSidebar',
          position: 'left',
          label: 'User Docs',
        },
        {
          type: 'docSidebar',
          sidebarId: 'developerSidebar',
          position: 'left',
          label: 'Developer Docs',
        },
      ],
    },
    footer: {
      style: 'dark',
      links: [
        {
          title: 'Docs',
          items: [
            {
              label: 'User guide',
              to: '/user/',
            },
            {
              label: 'Developer guide',
              to: '/developer/',
            },
          ],
        },
      ],
    },
  },
};

module.exports = config;
