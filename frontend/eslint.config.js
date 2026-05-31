/**
 * ESLint 配置
 * - Vue 3 + TypeScript
 */
import pluginVue from 'eslint-plugin-vue'
import vueTsEslintConfig from '@vue/eslint-config-typescript'

export default [
  {
    name: 'app/files-to-lint',
    files: ['**/*.{ts,mts,tsx,vue}'],
  },
  {
    name: 'app/files-to-ignore',
    ignores: ['**/dist/**', '**/dist-ssr/**', '**/coverage/**'],
  },
  ...pluginVue.configs['flat/essential'],
  ...vueTsEslintConfig(),
  {
    name: 'app/custom-rules',
    rules: {
      // 允许 console / debugger（开发阶段）
      'no-console': 'off',
      'no-debugger': 'off',
      // Vue 自定义规则
      'vue/multi-word-component-names': 'off',
    },
  },
]
