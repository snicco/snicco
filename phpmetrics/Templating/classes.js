var classes = [
    {
        "name": "Snicco\\Component\\Templating\\ViewComposer\\ViewComposerCollection",
        "interface": false,
        "abstract": false,
        "final": true,
        "methods": [
            {
                "name": "__construct",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "addComposer",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "compose",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "matchingComposers",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            }
        ],
        "nbMethodsIncludingGettersSetters": 4,
        "nbMethods": 4,
        "nbMethodsPrivate": 1,
        "nbMethodsPublic": 3,
        "nbMethodsGetter": 0,
        "nbMethodsSetters": 0,
        "wmc": 17,
        "ccn": 14,
        "ccnMethodMax": 6,
        "externals": [
            "Snicco\\Component\\Templating\\ViewComposer\\NewableInstanceViewComposerFactory",
            "Snicco\\Component\\Templating\\GlobalViewContext",
            "InvalidArgumentException",
            "InvalidArgumentException",
            "InvalidArgumentException",
            "Snicco\\Component\\Templating\\View\\View",
            "Snicco\\Component\\Templating\\View\\View",
            "Snicco\\Component\\Templating\\View\\View",
            "Snicco\\Component\\StrArr\\Str",
            "Snicco\\Component\\Templating\\ViewComposer\\ClosureViewComposer"
        ],
        "parents": [],
        "lcom": 1,
        "length": 92,
        "vocabulary": 21,
        "volume": 404.09,
        "difficulty": 6,
        "effort": 2424.56,
        "level": 0.17,
        "bugs": 0.13,
        "time": 135,
        "intelligentContent": 67.35,
        "number_operators": 20,
        "number_operands": 72,
        "number_operators_unique": 3,
        "number_operands_unique": 18,
        "cloc": 27,
        "loc": 82,
        "lloc": 55,
        "mi": 80.72,
        "mIwoC": 41.9,
        "commentWeight": 38.82,
        "kanDefect": 1.42,
        "relativeStructuralComplexity": 64,
        "relativeDataComplexity": 0.5,
        "relativeSystemComplexity": 64.5,
        "totalStructuralComplexity": 256,
        "totalDataComplexity": 2,
        "totalSystemComplexity": 258,
        "package": "Snicco\\Component\\Templating\\ViewComposer\\",
        "pageRank": 0,
        "afferentCoupling": 4,
        "efferentCoupling": 6,
        "instability": 0.6,
        "violations": {}
    },
    {
        "name": "Snicco\\Component\\Templating\\ViewComposer\\ClosureViewComposer",
        "interface": false,
        "abstract": false,
        "final": true,
        "methods": [
            {
                "name": "__construct",
                "role": "setter",
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "compose",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            }
        ],
        "nbMethodsIncludingGettersSetters": 2,
        "nbMethods": 1,
        "nbMethodsPrivate": 0,
        "nbMethodsPublic": 1,
        "nbMethodsGetter": 0,
        "nbMethodsSetters": 1,
        "wmc": 2,
        "ccn": 1,
        "ccnMethodMax": 1,
        "externals": [
            "Snicco\\Component\\Templating\\ViewComposer\\ViewComposer",
            "Closure",
            "Snicco\\Component\\Templating\\View\\View",
            "Snicco\\Component\\Templating\\View\\View"
        ],
        "parents": [],
        "lcom": 1,
        "length": 8,
        "vocabulary": 5,
        "volume": 18.58,
        "difficulty": 2,
        "effort": 37.15,
        "level": 0.5,
        "bugs": 0.01,
        "time": 2,
        "intelligentContent": 9.29,
        "number_operators": 2,
        "number_operands": 6,
        "number_operators_unique": 2,
        "number_operands_unique": 3,
        "cloc": 17,
        "loc": 30,
        "lloc": 13,
        "mi": 112.64,
        "mIwoC": 66.68,
        "commentWeight": 45.96,
        "kanDefect": 0.15,
        "relativeStructuralComplexity": 0,
        "relativeDataComplexity": 2,
        "relativeSystemComplexity": 2,
        "totalStructuralComplexity": 0,
        "totalDataComplexity": 4,
        "totalSystemComplexity": 4,
        "package": "Snicco\\Component\\Templating\\ViewComposer\\",
        "pageRank": 0,
        "afferentCoupling": 1,
        "efferentCoupling": 3,
        "instability": 0.75,
        "violations": {}
    },
    {
        "name": "Snicco\\Component\\Templating\\ViewComposer\\NewableInstanceViewComposerFactory",
        "interface": false,
        "abstract": false,
        "final": true,
        "methods": [
            {
                "name": "create",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            }
        ],
        "nbMethodsIncludingGettersSetters": 1,
        "nbMethods": 1,
        "nbMethodsPrivate": 0,
        "nbMethodsPublic": 1,
        "nbMethodsGetter": 0,
        "nbMethodsSetters": 0,
        "wmc": 2,
        "ccn": 2,
        "ccnMethodMax": 2,
        "externals": [
            "Snicco\\Component\\Templating\\ViewComposer\\ViewComposerFactory",
            "Snicco\\Component\\Templating\\ViewComposer\\ViewComposer",
            "composer",
            "Snicco\\Component\\Templating\\Exception\\BadViewComposer"
        ],
        "parents": [],
        "lcom": 1,
        "length": 12,
        "vocabulary": 8,
        "volume": 36,
        "difficulty": 1.67,
        "effort": 60,
        "level": 0.6,
        "bugs": 0.01,
        "time": 3,
        "intelligentContent": 21.6,
        "number_operators": 2,
        "number_operands": 10,
        "number_operators_unique": 2,
        "number_operands_unique": 6,
        "cloc": 0,
        "loc": 12,
        "lloc": 12,
        "mi": 65.29,
        "mIwoC": 65.29,
        "commentWeight": 0,
        "kanDefect": 0.15,
        "relativeStructuralComplexity": 1,
        "relativeDataComplexity": 1,
        "relativeSystemComplexity": 2,
        "totalStructuralComplexity": 1,
        "totalDataComplexity": 1,
        "totalSystemComplexity": 2,
        "package": "Snicco\\Component\\Templating\\ViewComposer\\",
        "pageRank": 0,
        "afferentCoupling": 1,
        "efferentCoupling": 4,
        "instability": 0.8,
        "violations": {}
    },
    {
        "name": "Snicco\\Component\\Templating\\OutputBuffer",
        "interface": false,
        "abstract": false,
        "final": true,
        "methods": [
            {
                "name": "start",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "get",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "remove",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            }
        ],
        "nbMethodsIncludingGettersSetters": 3,
        "nbMethods": 3,
        "nbMethodsPrivate": 0,
        "nbMethodsPublic": 3,
        "nbMethodsGetter": 0,
        "nbMethodsSetters": 0,
        "wmc": 6,
        "ccn": 4,
        "ccnMethodMax": 2,
        "externals": [
            "RuntimeException",
            "RuntimeException",
            "RuntimeException"
        ],
        "parents": [],
        "lcom": 3,
        "length": 20,
        "vocabulary": 9,
        "volume": 63.4,
        "difficulty": 4,
        "effort": 253.59,
        "level": 0.25,
        "bugs": 0.02,
        "time": 14,
        "intelligentContent": 15.85,
        "number_operators": 10,
        "number_operands": 10,
        "number_operators_unique": 4,
        "number_operands_unique": 5,
        "cloc": 18,
        "loc": 44,
        "lloc": 26,
        "mi": 97.8,
        "mIwoC": 55.98,
        "commentWeight": 41.83,
        "kanDefect": 0.36,
        "relativeStructuralComplexity": 0,
        "relativeDataComplexity": 1,
        "relativeSystemComplexity": 1,
        "totalStructuralComplexity": 0,
        "totalDataComplexity": 3,
        "totalSystemComplexity": 3,
        "package": "Snicco\\Component\\Templating\\",
        "pageRank": 0,
        "afferentCoupling": 2,
        "efferentCoupling": 1,
        "instability": 0.33,
        "violations": {}
    },
    {
        "name": "Snicco\\Component\\Templating\\ViewEngine",
        "interface": false,
        "abstract": false,
        "final": true,
        "methods": [
            {
                "name": "__construct",
                "role": "setter",
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "render",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "make",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "createFirstMatchingView",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            }
        ],
        "nbMethodsIncludingGettersSetters": 4,
        "nbMethods": 3,
        "nbMethodsPrivate": 1,
        "nbMethodsPublic": 2,
        "nbMethodsGetter": 0,
        "nbMethodsSetters": 1,
        "wmc": 7,
        "ccn": 4,
        "ccnMethodMax": 4,
        "externals": [
            "Snicco\\Component\\Templating\\ViewFactory\\ViewFactory",
            "Snicco\\Component\\Templating\\View\\View",
            "Snicco\\Component\\Templating\\View\\View",
            "Snicco\\Component\\Templating\\Exception\\ViewNotFound"
        ],
        "parents": [],
        "lcom": 1,
        "length": 36,
        "vocabulary": 15,
        "volume": 140.65,
        "difficulty": 3.63,
        "effort": 509.85,
        "level": 0.28,
        "bugs": 0.05,
        "time": 28,
        "intelligentContent": 38.8,
        "number_operators": 7,
        "number_operands": 29,
        "number_operators_unique": 3,
        "number_operands_unique": 12,
        "cloc": 21,
        "loc": 53,
        "lloc": 32,
        "mi": 92.98,
        "mIwoC": 51.59,
        "commentWeight": 41.39,
        "kanDefect": 0.61,
        "relativeStructuralComplexity": 16,
        "relativeDataComplexity": 1.05,
        "relativeSystemComplexity": 17.05,
        "totalStructuralComplexity": 64,
        "totalDataComplexity": 4.2,
        "totalSystemComplexity": 68.2,
        "package": "Snicco\\Component\\Templating\\",
        "pageRank": 0,
        "afferentCoupling": 4,
        "efferentCoupling": 3,
        "instability": 0.43,
        "violations": {}
    },
    {
        "name": "Snicco\\Component\\Templating\\Exception\\ViewNotFound",
        "interface": false,
        "abstract": false,
        "final": true,
        "methods": [],
        "nbMethodsIncludingGettersSetters": 0,
        "nbMethods": 0,
        "nbMethodsPrivate": 0,
        "nbMethodsPublic": 0,
        "nbMethodsGetter": 0,
        "nbMethodsSetters": 0,
        "wmc": 0,
        "ccn": 1,
        "ccnMethodMax": 0,
        "externals": [
            "RuntimeException"
        ],
        "parents": [
            "RuntimeException"
        ],
        "lcom": 0,
        "length": 0,
        "vocabulary": 0,
        "volume": 0,
        "difficulty": 0,
        "effort": 0,
        "level": 0,
        "bugs": 0,
        "time": 0,
        "intelligentContent": 0,
        "number_operators": 0,
        "number_operands": 0,
        "number_operators_unique": 0,
        "number_operands_unique": 0,
        "cloc": 0,
        "loc": 4,
        "lloc": 4,
        "mi": 171,
        "mIwoC": 171,
        "commentWeight": 0,
        "kanDefect": 0.15,
        "relativeStructuralComplexity": 0,
        "relativeDataComplexity": 0,
        "relativeSystemComplexity": 0,
        "totalStructuralComplexity": 0,
        "totalDataComplexity": 0,
        "totalSystemComplexity": 0,
        "package": "Snicco\\Component\\Templating\\Exception\\",
        "pageRank": 0,
        "afferentCoupling": 3,
        "efferentCoupling": 1,
        "instability": 0.25,
        "violations": {}
    },
    {
        "name": "Snicco\\Component\\Templating\\Exception\\BadViewComposer",
        "interface": false,
        "abstract": false,
        "final": true,
        "methods": [],
        "nbMethodsIncludingGettersSetters": 0,
        "nbMethods": 0,
        "nbMethodsPrivate": 0,
        "nbMethodsPublic": 0,
        "nbMethodsGetter": 0,
        "nbMethodsSetters": 0,
        "wmc": 0,
        "ccn": 1,
        "ccnMethodMax": 0,
        "externals": [
            "RuntimeException"
        ],
        "parents": [
            "RuntimeException"
        ],
        "lcom": 0,
        "length": 0,
        "vocabulary": 0,
        "volume": 0,
        "difficulty": 0,
        "effort": 0,
        "level": 0,
        "bugs": 0,
        "time": 0,
        "intelligentContent": 0,
        "number_operators": 0,
        "number_operands": 0,
        "number_operators_unique": 0,
        "number_operands_unique": 0,
        "cloc": 0,
        "loc": 4,
        "lloc": 4,
        "mi": 171,
        "mIwoC": 171,
        "commentWeight": 0,
        "kanDefect": 0.15,
        "relativeStructuralComplexity": 0,
        "relativeDataComplexity": 0,
        "relativeSystemComplexity": 0,
        "totalStructuralComplexity": 0,
        "totalDataComplexity": 0,
        "totalSystemComplexity": 0,
        "package": "Snicco\\Component\\Templating\\Exception\\",
        "pageRank": 0,
        "afferentCoupling": 1,
        "efferentCoupling": 1,
        "instability": 0.5,
        "violations": {}
    },
    {
        "name": "Snicco\\Component\\Templating\\Exception\\ViewCantBeRendered",
        "interface": false,
        "abstract": false,
        "final": true,
        "methods": [
            {
                "name": "fromPrevious",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            }
        ],
        "nbMethodsIncludingGettersSetters": 1,
        "nbMethods": 1,
        "nbMethodsPrivate": 0,
        "nbMethodsPublic": 1,
        "nbMethodsGetter": 0,
        "nbMethodsSetters": 0,
        "wmc": 1,
        "ccn": 1,
        "ccnMethodMax": 1,
        "externals": [
            "RuntimeException",
            "Snicco\\Component\\Templating\\Exception\\ViewCantBeRendered",
            "Throwable"
        ],
        "parents": [
            "RuntimeException"
        ],
        "lcom": 1,
        "length": 11,
        "vocabulary": 7,
        "volume": 30.88,
        "difficulty": 0.83,
        "effort": 25.73,
        "level": 1.2,
        "bugs": 0.01,
        "time": 1,
        "intelligentContent": 37.06,
        "number_operators": 1,
        "number_operands": 10,
        "number_operators_unique": 1,
        "number_operands_unique": 6,
        "cloc": 0,
        "loc": 8,
        "lloc": 8,
        "mi": 69.73,
        "mIwoC": 69.73,
        "commentWeight": 0,
        "kanDefect": 0.15,
        "relativeStructuralComplexity": 4,
        "relativeDataComplexity": 1,
        "relativeSystemComplexity": 5,
        "totalStructuralComplexity": 4,
        "totalDataComplexity": 1,
        "totalSystemComplexity": 5,
        "package": "Snicco\\Component\\Templating\\Exception\\",
        "pageRank": 0,
        "afferentCoupling": 4,
        "efferentCoupling": 4,
        "instability": 0.5,
        "violations": {}
    },
    {
        "name": "Snicco\\Component\\Templating\\GlobalViewContext",
        "interface": false,
        "abstract": false,
        "final": true,
        "methods": [
            {
                "name": "add",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "get",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "getArrayAccess",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            }
        ],
        "nbMethodsIncludingGettersSetters": 3,
        "nbMethods": 3,
        "nbMethodsPrivate": 1,
        "nbMethodsPublic": 2,
        "nbMethodsGetter": 0,
        "nbMethodsSetters": 0,
        "wmc": 5,
        "ccn": 3,
        "ccnMethodMax": 2,
        "externals": [
            "ArrayAccess",
            "Snicco\\Component\\StrArr\\Arr",
            "Snicco\\Component\\StrArr\\Arr",
            "BadMethodCallException",
            "BadMethodCallException",
            ""
        ],
        "parents": [],
        "lcom": 1,
        "length": 41,
        "vocabulary": 11,
        "volume": 141.84,
        "difficulty": 6,
        "effort": 851.02,
        "level": 0.17,
        "bugs": 0.05,
        "time": 47,
        "intelligentContent": 23.64,
        "number_operators": 9,
        "number_operands": 32,
        "number_operators_unique": 3,
        "number_operands_unique": 8,
        "cloc": 28,
        "loc": 73,
        "lloc": 45,
        "mi": 89.41,
        "mIwoC": 48.47,
        "commentWeight": 40.94,
        "kanDefect": 0.22,
        "relativeStructuralComplexity": 4,
        "relativeDataComplexity": 2,
        "relativeSystemComplexity": 6,
        "totalStructuralComplexity": 12,
        "totalDataComplexity": 6,
        "totalSystemComplexity": 18,
        "package": "Snicco\\Component\\Templating\\",
        "pageRank": 0,
        "afferentCoupling": 2,
        "efferentCoupling": 4,
        "instability": 0.67,
        "violations": {}
    },
    {
        "name": "Snicco\\Component\\Templating\\ViewFactory\\ChildContent",
        "interface": false,
        "abstract": false,
        "final": true,
        "methods": [
            {
                "name": "__construct",
                "role": "setter",
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "__toString",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            }
        ],
        "nbMethodsIncludingGettersSetters": 2,
        "nbMethods": 1,
        "nbMethodsPrivate": 0,
        "nbMethodsPublic": 1,
        "nbMethodsGetter": 0,
        "nbMethodsSetters": 1,
        "wmc": 2,
        "ccn": 1,
        "ccnMethodMax": 1,
        "externals": [
            "Closure",
            "Snicco\\Component\\Templating\\OutputBuffer",
            "Snicco\\Component\\Templating\\OutputBuffer"
        ],
        "parents": [],
        "lcom": 1,
        "length": 6,
        "vocabulary": 4,
        "volume": 12,
        "difficulty": 2,
        "effort": 24,
        "level": 0.5,
        "bugs": 0,
        "time": 1,
        "intelligentContent": 6,
        "number_operators": 2,
        "number_operands": 4,
        "number_operators_unique": 2,
        "number_operands_unique": 2,
        "cloc": 3,
        "loc": 18,
        "lloc": 15,
        "mi": 96.21,
        "mIwoC": 66.65,
        "commentWeight": 29.56,
        "kanDefect": 0.15,
        "relativeStructuralComplexity": 1,
        "relativeDataComplexity": 0.75,
        "relativeSystemComplexity": 1.75,
        "totalStructuralComplexity": 2,
        "totalDataComplexity": 1.5,
        "totalSystemComplexity": 3.5,
        "package": "Snicco\\Component\\Templating\\ViewFactory\\",
        "pageRank": 0,
        "afferentCoupling": 1,
        "efferentCoupling": 2,
        "instability": 0.67,
        "violations": {}
    },
    {
        "name": "Snicco\\Component\\Templating\\ViewFactory\\PHPViewFactory",
        "interface": false,
        "abstract": false,
        "final": true,
        "methods": [
            {
                "name": "__construct",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "make",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "renderPhpView",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "render",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "requireView",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "handleViewException",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            }
        ],
        "nbMethodsIncludingGettersSetters": 6,
        "nbMethods": 6,
        "nbMethodsPrivate": 3,
        "nbMethodsPublic": 3,
        "nbMethodsGetter": 0,
        "nbMethodsSetters": 0,
        "wmc": 9,
        "ccn": 4,
        "ccnMethodMax": 2,
        "externals": [
            "Snicco\\Component\\Templating\\ViewFactory\\ViewFactory",
            "Snicco\\Component\\Templating\\ViewFactory\\PHPViewFinder",
            "Snicco\\Component\\Templating\\ViewComposer\\ViewComposerCollection",
            "Snicco\\Component\\Templating\\View\\PHPView",
            "Snicco\\Component\\Templating\\View\\PHPView",
            "Snicco\\Component\\Templating\\View\\PHPView",
            "Snicco\\Component\\Templating\\OutputBuffer",
            "Snicco\\Component\\Templating\\OutputBuffer",
            "Snicco\\Component\\Templating\\View\\PHPView",
            "Snicco\\Component\\Templating\\ViewFactory\\ChildContent",
            "Snicco\\Component\\Templating\\View\\View",
            "Throwable",
            "Snicco\\Component\\Templating\\View\\PHPView",
            "Snicco\\Component\\Templating\\OutputBuffer",
            "Snicco\\Component\\Templating\\Exception\\ViewCantBeRendered"
        ],
        "parents": [],
        "lcom": 1,
        "length": 65,
        "vocabulary": 16,
        "volume": 260,
        "difficulty": 19.44,
        "effort": 5055.56,
        "level": 0.05,
        "bugs": 0.09,
        "time": 281,
        "intelligentContent": 13.37,
        "number_operators": 15,
        "number_operands": 50,
        "number_operators_unique": 7,
        "number_operands_unique": 9,
        "cloc": 9,
        "loc": 61,
        "lloc": 52,
        "mi": 73.15,
        "mIwoC": 45.12,
        "commentWeight": 28.03,
        "kanDefect": 0.45,
        "relativeStructuralComplexity": 169,
        "relativeDataComplexity": 0.39,
        "relativeSystemComplexity": 169.39,
        "totalStructuralComplexity": 1014,
        "totalDataComplexity": 2.36,
        "totalSystemComplexity": 1016.36,
        "package": "Snicco\\Component\\Templating\\ViewFactory\\",
        "pageRank": 0,
        "afferentCoupling": 2,
        "efferentCoupling": 9,
        "instability": 0.82,
        "violations": {}
    },
    {
        "name": "Snicco\\Component\\Templating\\ViewFactory\\PHPViewFinder",
        "interface": false,
        "abstract": false,
        "final": true,
        "methods": [
            {
                "name": "__construct",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "filePath",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "includeFile",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "normalize",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "normalizeViewName",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            }
        ],
        "nbMethodsIncludingGettersSetters": 5,
        "nbMethods": 5,
        "nbMethodsPrivate": 2,
        "nbMethodsPublic": 3,
        "nbMethodsGetter": 0,
        "nbMethodsSetters": 0,
        "wmc": 9,
        "ccn": 5,
        "ccnMethodMax": 4,
        "externals": [
            "Snicco\\Component\\Templating\\Exception\\ViewNotFound"
        ],
        "parents": [],
        "lcom": 2,
        "length": 68,
        "vocabulary": 20,
        "volume": 293.89,
        "difficulty": 8.5,
        "effort": 2498.07,
        "level": 0.12,
        "bugs": 0.1,
        "time": 139,
        "intelligentContent": 34.58,
        "number_operators": 17,
        "number_operands": 51,
        "number_operators_unique": 5,
        "number_operands_unique": 15,
        "cloc": 19,
        "loc": 62,
        "lloc": 43,
        "mi": 84.23,
        "mIwoC": 46.41,
        "commentWeight": 37.81,
        "kanDefect": 0.52,
        "relativeStructuralComplexity": 4,
        "relativeDataComplexity": 1.73,
        "relativeSystemComplexity": 5.73,
        "totalStructuralComplexity": 20,
        "totalDataComplexity": 8.67,
        "totalSystemComplexity": 28.67,
        "package": "Snicco\\Component\\Templating\\ViewFactory\\",
        "pageRank": 0,
        "afferentCoupling": 2,
        "efferentCoupling": 1,
        "instability": 0.33,
        "violations": {}
    },
    {
        "name": "Snicco\\Component\\Templating\\View\\PHPView",
        "interface": false,
        "abstract": false,
        "final": true,
        "methods": [
            {
                "name": "__construct",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "path",
                "role": "getter",
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "name",
                "role": "getter",
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "render",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "with",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "context",
                "role": "getter",
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "parent",
                "role": "getter",
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "parseParentView",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "parseExtends",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            }
        ],
        "nbMethodsIncludingGettersSetters": 9,
        "nbMethods": 5,
        "nbMethodsPrivate": 2,
        "nbMethodsPublic": 3,
        "nbMethodsGetter": 4,
        "nbMethodsSetters": 0,
        "wmc": 16,
        "ccn": 8,
        "ccnMethodMax": 6,
        "externals": [
            "Snicco\\Component\\Templating\\View\\View",
            "Snicco\\Component\\Templating\\ViewFactory\\PHPViewFactory",
            "Snicco\\Component\\Templating\\View\\View",
            "RuntimeException",
            "Snicco\\Component\\StrArr\\Str",
            "RuntimeException"
        ],
        "parents": [],
        "lcom": 1,
        "length": 105,
        "vocabulary": 30,
        "volume": 515.22,
        "difficulty": 5.31,
        "effort": 2734.65,
        "level": 0.19,
        "bugs": 0.17,
        "time": 152,
        "intelligentContent": 97.07,
        "number_operators": 36,
        "number_operands": 69,
        "number_operators_unique": 4,
        "number_operands_unique": 26,
        "cloc": 26,
        "loc": 103,
        "lloc": 78,
        "mi": 73.77,
        "mIwoC": 38.66,
        "commentWeight": 35.11,
        "kanDefect": 0.64,
        "relativeStructuralComplexity": 36,
        "relativeDataComplexity": 1.79,
        "relativeSystemComplexity": 37.79,
        "totalStructuralComplexity": 324,
        "totalDataComplexity": 16.14,
        "totalSystemComplexity": 340.14,
        "package": "Snicco\\Component\\Templating\\View\\",
        "pageRank": 0,
        "afferentCoupling": 1,
        "efferentCoupling": 4,
        "instability": 0.8,
        "violations": {}
    }
]