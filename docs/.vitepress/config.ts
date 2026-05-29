import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'PHP Soundfile',
  description: 'Low-level audio I/O and resampling for PHP — backed by libsoundfile and libsamplerate',

  base: '/soundfile/',

  head: [
    ['link', { rel: 'icon', href: '/favicon.ico' }],
    ['meta', { name: 'theme-color', content: '#3c873a' }],
    ['meta', { name: 'og:type', content: 'website' }],
    ['meta', { name: 'og:locale', content: 'en' }],
    ['meta', { name: 'og:site_name', content: 'SndFile PHP' }],
  ],

  themeConfig: {
    nav: [
      { text: 'User Guide', link: '/guide/getting-started/what-is-soundfile' },
      { text: 'API Reference', link: '/api/' },
      {
        text: '1.0.0',
        items: [
          { text: 'Changelog', link: '/changelog' },
          { text: 'GitHub', link: 'https://github.com/phpmlkit/soundfile' },
        ]
      }
    ],

    sidebar: {
      '/guide/': [
        {
          text: 'Getting Started',
          collapsed: false,
          items: [
            { text: 'What is SndFile?', link: '/guide/getting-started/what-is-soundfile' },
            { text: 'Installation', link: '/guide/getting-started/installation' },
            { text: 'Quick Start', link: '/guide/getting-started/quick-start' },
          ]
        },
        {
          text: 'Fundamentals',
          collapsed: false,
          items: [
            { text: 'Reading and Writing', link: '/guide/fundamentals/reading-and-writing' },
            { text: 'Streaming with SndFile', link: '/guide/fundamentals/streaming-with-soundfile' },
            { text: 'Resampling', link: '/guide/fundamentals/resampling' },
            { text: 'Formats and DTypes', link: '/guide/fundamentals/formats-and-dtypes' },
            { text: 'Metadata', link: '/guide/fundamentals/metadata' },
          ]
        },
        {
          text: 'Advanced',
          collapsed: true,
          items: [
            { text: 'Using FFI Handles Directly', link: '/guide/advanced/ffi-handles' },
            { text: 'Troubleshooting', link: '/guide/advanced/troubleshooting'},
            {text: 'Performance', link: '/guide/advanced/performance'}
          ]
        },
      ],
      '/api/': [
        {
          text: 'API Reference',
          items: [
            { text: 'Overview', link: '/api/' },
            { text: 'Global Functions', link: '/api/global-functions' },
          ]
        },
        {
          text: 'Classes',
          items: [
            { text: 'SoundFile', link: '/api/soundfile-class' },
            { text: 'SfInfo', link: '/api/sf-info' },
            { text: 'SfMetadata', link: '/api/sf-metadata' },
          ]
        },
        {
          text: 'Types',
          items: [
            { text: 'Enums', link: '/api/enums' },
            { text: 'Exceptions', link: '/api/exceptions' },
          ]
        }
      ]
    },

    socialLinks: [
      { icon: 'github', link: 'https://github.com/phpmlkit/soundfile' },
    ],

    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright © 2026 CodeWithKyrian'
    },

    search: {
      provider: 'local'
    },

    editLink: {
      pattern: 'https://github.com/phpmlkit/soundfile/edit/main/docs/:path',
      text: 'Edit this page on GitHub'
    },

    lastUpdated: {
      text: 'Updated at',
      formatOptions: {
        dateStyle: 'full',
        timeStyle: 'medium'
      }
    }
  }
})
