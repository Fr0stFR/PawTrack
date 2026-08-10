import path from 'node:path'
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  resolve: {
    // '@' pointe toujours sur src/ : plus de '../../' qui s'allongent
    // quand on descend dans features/xxx/
    alias: { '@': path.resolve(__dirname, 'src') },
  },
})
