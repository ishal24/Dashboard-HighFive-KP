import { createInertiaApp } from '@inertiajs/react'
import { createRoot } from 'react-dom/client'

createInertiaApp({
  resolve: (name) => import(`./pages/${name}.jsx`), // Pages go in resources/js/Pages
  setup({ el, App, props }) {
    createRoot(el).render(<App {...props} />)
  },
})
