const scopes = require('./commit-scopes.json');

module.exports = {
    extends: ['@commitlint/config-conventional'],
    rules: {
        'type-enum': [
            2,
            'always',
            [
                'ci',
                'docs',
                'feat',
                'fix',
                'perf',
                'refactor',
                'style',
                'revert',
                'test',
                'chore'
            ],
        ],
        'subject-case': [2, 'always', 'lower-case'],
        'subject-exclamation-mark': [2, 'never', '!'],
        'scope-case': [2, 'always', 'lower-case'],
        'scope-enum': [2, 'always', scopes],
        'scope-empty': [2, 'never'],
        'header-max-length': [2, 'always', 72],
        'body-max-line-length': [2, 'always', 80],
        'body-case': [2, 'always', 'sentence-case'],
        'footer-max-line-length': [2, 'always', 80],
    },
    prompt: {
        messages: {
            skip: '(skip)',
            max: 'max %d chars',
            min: 'min %d chars',
            emptyWarning: 'can not be empty',
            upperLimitWarning: 'over limit',
            lowerLimitWarning: 'below limit'
        },
        "questions": {
            type: {
                description: "Select the type of change that you're committing",
                enum: {
                    feat: {
                        description: 'A new feature',
                        title: 'Features',
                        emoji: '✨',
                    },
                    fix: {
                        description: 'A bug fix',
                        title: 'Bug Fixes',
                        emoji: '🐛',
                    },
                    docs: {
                        description: 'Documentation only changes',
                        title: 'Documentation',
                        emoji: '📚',
                    },
                    style: {
                        description:
                            'Changes that do not affect the meaning of the code (white-space, formatting, missing semi-colons, etc)',
                        title: 'Styles',
                        emoji: '💎',
                    },
                    refactor: {
                        description:
                            'A code change in src/* that neither fixes a bug nor adds a feature',
                        title: 'Code Refactoring',
                        emoji: '📦',
                    },
                    perf: {
                        description: 'A code change that improves performance',
                        title: 'Performance Improvements',
                        emoji: '🚀',
                    },
                    test: {
                        description: 'Adding missing tests or correcting existing tests',
                        title: 'Tests',
                        emoji: '🚨',
                    },
                    ci: {
                        description:
                            'Changes to our CI configuration files and scripts',
                        title: 'Continuous Integrations',
                        emoji: '⚙️',
                    },
                    chore: {
                        description: "Any other changes that don't modify src or test files (example: changing .gitignore)",
                        title: 'Chores',
                        emoji: '♻️',
                    },
                    revert: {
                        description: 'Reverts a previous commit',
                        title: 'Reverts',
                        emoji: '🗑',
                    },
                },
            },
            body: {
                description: 'Provide a longer description of the change (max. 80 chars per line. Use \\n to insert a new line.).',
            }
        }
    }
};
