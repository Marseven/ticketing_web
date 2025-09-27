#!/usr/bin/env node

/**
 * Script pour corriger automatiquement la gestion CSRF dans tous les formulaires Vue.js
 */

const fs = require('fs')
const path = require('path')

// Répertoires à analyser
const directories = [
  'resources/js/pages/admin',
  'resources/js/pages/organizer', 
  'resources/js/pages/auth',
  'resources/js/pages/account',
  'resources/js/pages',
  'resources/js/components'
]

// Modèles de remplacement
const replacements = [
  // Remplacer fetch() basique par api utils
  {
    pattern: /fetch\(['"`]([^'"`]+)['"`],\s*{\s*method:\s*['"`](POST|PUT|PATCH|DELETE)['"`]/g,
    replacement: "api.$2.toLowerCase()('$1'"
  },
  
  // Ajouter import api utils si fetch est utilisé
  {
    pattern: /(<script>|<script setup>)/,
    replacement: "$1\nimport { api, handleApiError, showSuccess } from '@/utils/apiUtils.js'"
  },
  
  // Remplacer alert() par Toast
  {
    pattern: /alert\(['"`]([^'"`]+)['"`]\)/g,
    replacement: "Toast.fire({ icon: 'success', title: '$1' })"
  },
  
  // Remplacer confirm() par confirmAction
  {
    pattern: /confirm\(['"`]([^'"`]+)['"`]\)/g,
    replacement: "await confirmAction('Confirmation', '$1')"
  }
]

function processFile(filePath) {
  try {
    let content = fs.readFileSync(filePath, 'utf8')
    let modified = false
    
    // Vérifier si le fichier contient des formulaires ou des appels API
    const hasForm = /(@submit|fetch\(|axios\.|\.post\(|\.put\(|\.delete\()/.test(content)
    
    if (!hasForm) {
      return false
    }
    
    console.log(`📝 Traitement: ${filePath}`)
    
    // Appliquer les remplacements
    replacements.forEach(({ pattern, replacement }) => {
      const newContent = content.replace(pattern, replacement)
      if (newContent !== content) {
        content = newContent
        modified = true
      }
    })
    
    // Ajouter les headers CSRF si fetch est utilisé sans api utils
    if (/fetch\s*\(['"`][^'"`]+['"`],\s*{/.test(content) && 
        !content.includes('X-CSRF-TOKEN')) {
      
      const fetchPattern = /(headers:\s*{[^}]*)(})/g
      content = content.replace(fetchPattern, (match, headers, closing) => {
        if (!headers.includes('X-CSRF-TOKEN')) {
          const newHeaders = headers + ",\n            'X-Requested-With': 'XMLHttpRequest',\n            'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content')"
          return newHeaders + closing
        }
        return match
      })
      modified = true
    }
    
    if (modified) {
      fs.writeFileSync(filePath, content)
      console.log(`✅ Modifié: ${filePath}`)
      return true
    }
    
    return false
  } catch (error) {
    console.error(`❌ Erreur traitement ${filePath}:`, error.message)
    return false
  }
}

function processDirectory(dirPath) {
  const fullPath = path.resolve(dirPath)
  
  if (!fs.existsSync(fullPath)) {
    console.log(`⚠️  Répertoire introuvable: ${dirPath}`)
    return
  }
  
  const files = fs.readdirSync(fullPath, { withFileTypes: true })
  
  files.forEach(file => {
    const filePath = path.join(fullPath, file.name)
    
    if (file.isDirectory()) {
      processDirectory(filePath)
    } else if (file.name.endsWith('.vue')) {
      processFile(filePath)
    }
  })
}

// Main execution
console.log('🚀 Démarrage de la correction CSRF...\n')

let totalProcessed = 0
directories.forEach(dir => {
  console.log(`📁 Traitement du répertoire: ${dir}`)
  processDirectory(dir)
  console.log('')
})

console.log('✨ Correction CSRF terminée!')
console.log('\n📋 Actions à effectuer manuellement:')
console.log('1. Vérifier que l\'import apiUtils est correct dans chaque fichier')
console.log('2. Tester chaque formulaire pour s\'assurer qu\'il fonctionne')
console.log('3. Adapter les gestions d\'erreur spécifiques si nécessaire')
console.log('4. Compiler les assets: npm run build')