#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const cssPath = path.join(root, 'frontend', 'web', 'css', 'design-system.css');
const referenceRoots = [
  path.join(root, 'frontend', 'views'),
  path.join(root, 'frontend', 'modules'),
  path.join(root, 'frontend', 'widgets'),
  path.join(root, 'frontend', 'web', 'js'),
];

function lineNumber(text, index) {
  return text.slice(0, index).split('\n').length;
}

function stripComments(text) {
  return text.replace(/\/\*[\s\S]*?\*\//g, (comment) => comment.replace(/[^\n]/g, ' '));
}

function splitSelectorList(selectorText) {
  const selectors = [];
  let start = 0;
  let parentheses = 0;
  let quote = null;

  for (let index = 0; index < selectorText.length; index += 1) {
    const character = selectorText[index];
    if (quote) {
      if (character === quote && selectorText[index - 1] !== '\\') quote = null;
      continue;
    }
    if (character === '"' || character === "'") {
      quote = character;
    } else if (character === '(') {
      parentheses += 1;
    } else if (character === ')') {
      parentheses = Math.max(0, parentheses - 1);
    } else if (character === ',' && parentheses === 0) {
      selectors.push(selectorText.slice(start, index));
      start = index + 1;
    }
  }

  selectors.push(selectorText.slice(start));
  return selectors.map((selector) => selector.replace(/\s+/g, ' ').trim()).filter(Boolean);
}

function parseRules(css) {
  const rules = [];
  const stack = [];
  let bufferStart = 0;
  let quote = null;
  let parentheses = 0;
  let comment = false;

  for (let index = 0; index < css.length; index += 1) {
    const character = css[index];
    const next = css[index + 1];

    if (comment) {
      if (character === '*' && next === '/') {
        comment = false;
        index += 1;
      }
      continue;
    }
    if (quote) {
      if (character === quote && css[index - 1] !== '\\') quote = null;
      continue;
    }
    if (character === '/' && next === '*') {
      comment = true;
      index += 1;
      continue;
    }
    if (character === '"' || character === "'") {
      quote = character;
      continue;
    }
    if (character === '(') {
      parentheses += 1;
      continue;
    }
    if (character === ')') {
      parentheses = Math.max(0, parentheses - 1);
      continue;
    }
    if (parentheses > 0) continue;

    if (character === '{') {
      const prelude = css.slice(bufferStart, index).trim();
      stack.push({ prelude, start: bufferStart, open: index });
      bufferStart = index + 1;
      continue;
    }
    if (character === '}') {
      const current = stack.pop();
      if (current && current.prelude && !current.prelude.startsWith('@')) {
        const body = css.slice(current.open + 1, index).replace(/\s+/g, ' ').trim();
        if (body && !body.includes('{')) {
          const selectors = splitSelectorList(current.prelude);
          for (const selector of selectors) {
            rules.push({
              selector,
              body,
              line: lineNumber(css, current.start),
            });
          }
        }
      }
      bufferStart = index + 1;
    }
  }

  return rules;
}

function collectFiles(directory) {
  if (!fs.existsSync(directory)) return [];
  const files = [];
  for (const entry of fs.readdirSync(directory, { withFileTypes: true })) {
    const entryPath = path.join(directory, entry.name);
    if (entry.isDirectory()) files.push(...collectFiles(entryPath));
    else if (/\.(php|js|html?)$/i.test(entry.name)) files.push(entryPath);
  }
  return files;
}

function collectReferences() {
  let content = '';
  for (const directory of referenceRoots) {
    for (const file of collectFiles(directory)) content += `\n${fs.readFileSync(file, 'utf8')}`;
  }
  return content;
}

function selectorTokens(selector) {
  return [...selector.matchAll(/([.#])([_a-zA-Z][\w-]*)/g)].map((match) => `${match[1]}${match[2]}`);
}

function formatGroup(group) {
  return group.map((rule) => `    line ${rule.line}: ${rule.selector}`).join('\n');
}

const css = stripComments(fs.readFileSync(cssPath, 'utf8'));
const rules = parseRules(css);
const references = collectReferences();
const selectorGroups = new Map();
const bodyGroups = new Map();

for (const rule of rules) {
  if (!selectorGroups.has(rule.selector)) selectorGroups.set(rule.selector, []);
  selectorGroups.get(rule.selector).push(rule);

  if (!bodyGroups.has(rule.body)) bodyGroups.set(rule.body, []);
  bodyGroups.get(rule.body).push(rule);
}

const duplicates = [...selectorGroups.entries()].filter(([, group]) => group.length > 1);
const exactDuplicates = [...bodyGroups.values()].flatMap((group) => {
  const bySelector = new Map();
  for (const rule of group) {
    if (!bySelector.has(rule.selector)) bySelector.set(rule.selector, []);
    bySelector.get(rule.selector).push(rule);
  }
  return [...bySelector.values()].filter((rulesForSelector) => rulesForSelector.length > 1);
});
const equivalentRules = [...bodyGroups.values()].filter((group) => new Set(group.map((rule) => rule.selector)).size > 1);
const unused = [...selectorGroups.keys()].filter((selector) => {
  const tokens = selectorTokens(selector);
  return tokens.length > 0 && tokens.every((token) => !references.includes(token.slice(1)));
});

console.log(`CSS duplicate check: ${path.relative(root, cssPath)}`);
console.log(`Parsed rules: ${rules.length}`);
console.log(`Selectors defined more than once: ${duplicates.length}`);
for (const [selector, group] of duplicates) {
  console.log(`\n${selector}`);
  console.log(formatGroup(group));
}

console.log(`\nExact duplicate rules (same selector and body): ${exactDuplicates.length}`);
for (const group of exactDuplicates) {
  console.log(`\n${group[0].selector}`);
  console.log(formatGroup(group));
}

console.log(`\nEquivalent rule groups (same body, different selectors): ${equivalentRules.length}`);
for (const group of equivalentRules) {
  console.log(`\n  lines ${group.map((rule) => rule.line).join(', ')}: ${group.map((rule) => rule.selector).join(' | ')}`);
}

console.log(`\nClass/id selectors with no view/JS reference: ${unused.length}`);
for (const selector of unused) console.log(`  ${selector}`);

process.exitCode = duplicates.length > 0 || exactDuplicates.length > 0 || equivalentRules.length > 0 ? 1 : 0;
