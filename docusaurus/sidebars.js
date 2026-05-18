const sidebars = {
  homeSidebar: ['home'],
  userSidebar: [
    {
      type: 'category',
      label: 'User Documentation',
      link: {
        type: 'doc',
        id: 'user/index',
      },
      items: [
        'user/storefront-guide',
        'user/admin-guide',
        'user/feature-coverage',
      ],
    },
  ],
  developerSidebar: [
    {
      type: 'category',
      label: 'Developer Documentation',
      link: {
        type: 'doc',
        id: 'developer/index',
      },
      items: [
        'developer/architecture',
        'developer/module-development',
        'developer/testing-and-verification',
      ],
    },
  ],
};

module.exports = sidebars;
