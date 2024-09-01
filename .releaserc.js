module.exports = {
    branches: [
        "master",
        { name: 'beta', prerelease: true }
    ],
    repositoryUrl: "https://github.com/snicco/snicco.git",
    tagFormat: "v${version}",
    plugins: [
        ["@semantic-release/commit-analyzer", {
            preset: "angular",
            parserOpts: {
                noteKeywords: ["BREAKING CHANGE", "BREAKING CHANGES", "BREAKING"]
            }
        }],
        ["@semantic-release/release-notes-generator", {
            preset: "angular",
            parserOpts: {
                noteKeywords: ["BREAKING CHANGE", "BREAKING CHANGES", "BREAKING"]
            },
            writerOpts: {
                groupBy: 'type',
                commitGroupsSort: 'title',
                commitsSort: ['scope', 'subject'],
                noteGroupsSort: 'title',
            }
        }],
        ["@semantic-release/exec", {
            prepareCmd: "./bin/prepare-composer.sh ${nextRelease.version}",
        }],
        "@semantic-release/changelog",
        "@semantic-release/github",
        ["@semantic-release/git", {
            assets: [
                "CHANGELOG.md",
                "src/**/composer.json",
                "monorepo-builder.php"
            ],
            message: "chore(monorepo): release v${nextRelease.version}"
        }]
    ]
};