var classes = [
    {
        "name": "Snicco\\Bridge\\Blade\\BladeStandalone",
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
                "name": "getBladeViewFactory",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "boostrap",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "bindWordPressDirectives",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "bindDependencies",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "bootIlluminateViewServiceProvider",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "listenToEvents",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "disableUnsupportedDirectives",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "bindFrameworkDependencies",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            }
        ],
        "nbMethodsIncludingGettersSetters": 9,
        "nbMethods": 9,
        "nbMethodsPrivate": 5,
        "nbMethodsPublic": 4,
        "nbMethodsGetter": 0,
        "nbMethodsSetters": 0,
        "wmc": 15,
        "ccn": 7,
        "ccnMethodMax": 5,
        "externals": [
            "Snicco\\Component\\Templating\\ViewComposer\\ViewComposerCollection",
            "Snicco\\Bridge\\Blade\\BladeViewComposer",
            "Illuminate\\Container\\Container",
            "Illuminate\\Support\\Facades\\Facade",
            "Illuminate\\Support\\Facades\\Facade",
            "Snicco\\Bridge\\Blade\\BladeViewFactory",
            "Snicco\\Component\\BetterWPAPI\\BetterWPAPI",
            "Snicco\\Component\\BetterWPAPI\\BetterWPAPI",
            "Illuminate\\Support\\Facades\\Blade",
            "Illuminate\\Support\\Facades\\Blade",
            "Illuminate\\Support\\Facades\\Blade",
            "Illuminate\\Support\\Fluent",
            "Illuminate\\Filesystem\\Filesystem",
            "Illuminate\\Events\\Dispatcher",
            "Snicco\\Bridge\\Blade\\DummyApplication",
            "Illuminate\\View\\ViewServiceProvider",
            "BadMethodCallException",
            "Illuminate\\Support\\Facades\\Blade",
            "BadMethodCallException",
            "Illuminate\\Support\\Facades\\Blade",
            "BadMethodCallException",
            "Illuminate\\Support\\Facades\\Blade",
            "Snicco\\Bridge\\Blade\\BladeViewFactory"
        ],
        "parents": [],
        "implements": [],
        "lcom": 2,
        "length": 108,
        "vocabulary": 33,
        "volume": 544.79,
        "difficulty": 7.59,
        "effort": 4134.6,
        "level": 0.13,
        "bugs": 0.18,
        "time": 230,
        "intelligentContent": 71.78,
        "number_operators": 23,
        "number_operands": 85,
        "number_operators_unique": 5,
        "number_operands_unique": 28,
        "cloc": 27,
        "loc": 114,
        "lloc": 87,
        "mi": 71.82,
        "mIwoC": 37.59,
        "commentWeight": 34.23,
        "kanDefect": 0.36,
        "relativeStructuralComplexity": 289,
        "relativeDataComplexity": 0.19,
        "relativeSystemComplexity": 289.19,
        "totalStructuralComplexity": 2601,
        "totalDataComplexity": 1.72,
        "totalSystemComplexity": 2602.72,
        "package": "Snicco\\Bridge\\Blade\\",
        "pageRank": 0,
        "afferentCoupling": 1,
        "efferentCoupling": 13,
        "instability": 0.93,
        "violations": {}
    },
    {
        "name": "Snicco\\Bridge\\Blade\\BladeComponent",
        "interface": false,
        "abstract": true,
        "final": false,
        "methods": [
            {
                "name": "render",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "componentName",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            }
        ],
        "nbMethodsIncludingGettersSetters": 2,
        "nbMethods": 2,
        "nbMethodsPrivate": 1,
        "nbMethodsPublic": 1,
        "nbMethodsGetter": 0,
        "nbMethodsSetters": 0,
        "wmc": 2,
        "ccn": 1,
        "ccnMethodMax": 1,
        "externals": [
            "Illuminate\\View\\Component"
        ],
        "parents": [
            "Illuminate\\View\\Component"
        ],
        "implements": [],
        "lcom": 2,
        "length": 9,
        "vocabulary": 6,
        "volume": 23.26,
        "difficulty": 1.75,
        "effort": 40.71,
        "level": 0.57,
        "bugs": 0.01,
        "time": 2,
        "intelligentContent": 13.29,
        "number_operators": 2,
        "number_operands": 7,
        "number_operators_unique": 2,
        "number_operands_unique": 4,
        "cloc": 4,
        "loc": 14,
        "lloc": 10,
        "mi": 105.31,
        "mIwoC": 68.48,
        "commentWeight": 36.83,
        "kanDefect": 0.15,
        "relativeStructuralComplexity": 0,
        "relativeDataComplexity": 1.5,
        "relativeSystemComplexity": 1.5,
        "totalStructuralComplexity": 0,
        "totalDataComplexity": 3,
        "totalSystemComplexity": 3,
        "package": "Snicco\\Bridge\\Blade\\",
        "pageRank": 0,
        "afferentCoupling": 0,
        "efferentCoupling": 1,
        "instability": 1,
        "violations": {}
    },
    {
        "name": "Snicco\\Bridge\\Blade\\DummyApplication",
        "interface": false,
        "abstract": false,
        "final": true,
        "methods": [
            {
                "name": "getNamespace",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "version",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "basePath",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "bootstrapPath",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "configPath",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "databasePath",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "resourcePath",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "storagePath",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "environment",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "runningInConsole",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "runningUnitTests",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "isDownForMaintenance",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "registerConfiguredProviders",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "register",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "registerDeferredProvider",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "resolveProvider",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "boot",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "booting",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "booted",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "bootstrapWith",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "getLocale",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "getProviders",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "hasBeenBootstrapped",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "loadDeferredProviders",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "setLocale",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "shouldSkipMiddleware",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "terminate",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "bound",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "alias",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "tag",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "tagged",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "bind",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "bindIf",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "singleton",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "singletonIf",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "extend",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "instance",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "addContextualBinding",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "when",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "factory",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "flush",
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
                "name": "call",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "resolved",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "resolving",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "afterResolving",
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
                "name": "has",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            }
        ],
        "nbMethodsIncludingGettersSetters": 48,
        "nbMethods": 48,
        "nbMethodsPrivate": 0,
        "nbMethodsPublic": 48,
        "nbMethodsGetter": 0,
        "nbMethodsSetters": 0,
        "wmc": 48,
        "ccn": 1,
        "ccnMethodMax": 1,
        "externals": [
            "Illuminate\\Contracts\\Foundation\\Application",
            "BadMethodCallException",
            "BadMethodCallException",
            "BadMethodCallException",
            "BadMethodCallException",
            "BadMethodCallException",
            "BadMethodCallException",
            "BadMethodCallException",
            "BadMethodCallException",
            "BadMethodCallException",
            "BadMethodCallException",
            "BadMethodCallException",
            "BadMethodCallException",
            "BadMethodCallException",
            "BadMethodCallException",
            "BadMethodCallException",
            "BadMethodCallException",
            "BadMethodCallException",
            "BadMethodCallException",
            "BadMethodCallException",
            "BadMethodCallException",
            "BadMethodCallException",
            "BadMethodCallException",
            "BadMethodCallException",
            "BadMethodCallException",
            "BadMethodCallException",
            "BadMethodCallException",
            "BadMethodCallException",
            "BadMethodCallException",
            "BadMethodCallException",
            "BadMethodCallException",
            "BadMethodCallException",
            "BadMethodCallException",
            "BadMethodCallException",
            "BadMethodCallException",
            "Closure",
            "BadMethodCallException",
            "BadMethodCallException",
            "BadMethodCallException",
            "BadMethodCallException",
            "BadMethodCallException",
            "BadMethodCallException",
            "BadMethodCallException",
            "BadMethodCallException",
            "BadMethodCallException",
            "Closure",
            "BadMethodCallException",
            "Closure",
            "BadMethodCallException",
            "BadMethodCallException",
            "BadMethodCallException"
        ],
        "parents": [],
        "implements": [
            "Illuminate\\Contracts\\Foundation\\Application"
        ],
        "lcom": 48,
        "length": 107,
        "vocabulary": 69,
        "volume": 653.61,
        "difficulty": 0.78,
        "effort": 509.43,
        "level": 1.28,
        "bugs": 0.22,
        "time": 28,
        "intelligentContent": 838.6,
        "number_operators": 1,
        "number_operands": 106,
        "number_operators_unique": 1,
        "number_operands_unique": 68,
        "cloc": 12,
        "loc": 208,
        "lloc": 196,
        "mi": 48.33,
        "mIwoC": 30.15,
        "commentWeight": 18.18,
        "kanDefect": 0.15,
        "relativeStructuralComplexity": 0,
        "relativeDataComplexity": 2.1,
        "relativeSystemComplexity": 2.1,
        "totalStructuralComplexity": 0,
        "totalDataComplexity": 101,
        "totalSystemComplexity": 101,
        "package": "Snicco\\Bridge\\Blade\\",
        "pageRank": 0,
        "afferentCoupling": 1,
        "efferentCoupling": 3,
        "instability": 0.75,
        "violations": {}
    },
    {
        "name": "Snicco\\Bridge\\Blade\\BladeViewComposer",
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
                "name": "handleEvent",
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
        "wmc": 3,
        "ccn": 3,
        "ccnMethodMax": 3,
        "externals": [
            "Snicco\\Component\\Templating\\ViewComposer\\ViewComposerCollection",
            "RuntimeException",
            "Snicco\\Component\\Templating\\ValueObject\\FilePath",
            "Snicco\\Component\\Templating\\ValueObject\\View"
        ],
        "parents": [],
        "implements": [],
        "lcom": 1,
        "length": 31,
        "vocabulary": 12,
        "volume": 111.13,
        "difficulty": 4,
        "effort": 444.54,
        "level": 0.25,
        "bugs": 0.04,
        "time": 25,
        "intelligentContent": 27.78,
        "number_operators": 7,
        "number_operands": 24,
        "number_operators_unique": 3,
        "number_operands_unique": 9,
        "cloc": 1,
        "loc": 21,
        "lloc": 20,
        "mi": 73.47,
        "mIwoC": 56.89,
        "commentWeight": 16.58,
        "kanDefect": 0.22,
        "relativeStructuralComplexity": 49,
        "relativeDataComplexity": 0.19,
        "relativeSystemComplexity": 49.19,
        "totalStructuralComplexity": 98,
        "totalDataComplexity": 0.38,
        "totalSystemComplexity": 98.38,
        "package": "Snicco\\Bridge\\Blade\\",
        "pageRank": 0,
        "afferentCoupling": 1,
        "efferentCoupling": 4,
        "instability": 0.8,
        "violations": {}
    },
    {
        "name": "Snicco\\Bridge\\Blade\\BladeViewFactory",
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
                "name": "toString",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "normalizeName",
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
        "wmc": 8,
        "ccn": 5,
        "ccnMethodMax": 3,
        "externals": [
            "Snicco\\Component\\Templating\\ViewFactory\\ViewFactory",
            "Illuminate\\View\\Factory",
            "Snicco\\Component\\Templating\\ValueObject\\View",
            "Snicco\\Component\\Templating\\ValueObject\\FilePath",
            "Snicco\\Component\\Templating\\ValueObject\\View",
            "Snicco\\Component\\Templating\\Exception\\ViewNotFound",
            "Snicco\\Component\\Templating\\ValueObject\\View",
            "Snicco\\Component\\Templating\\Exception\\ViewCantBeRendered",
            "Illuminate\\Support\\Str",
            "Illuminate\\Support\\Str",
            "Illuminate\\Support\\Str"
        ],
        "parents": [],
        "implements": [
            "Snicco\\Component\\Templating\\ViewFactory\\ViewFactory"
        ],
        "lcom": 1,
        "length": 66,
        "vocabulary": 18,
        "volume": 275.22,
        "difficulty": 9.42,
        "effort": 2593.37,
        "level": 0.11,
        "bugs": 0.09,
        "time": 144,
        "intelligentContent": 29.21,
        "number_operators": 17,
        "number_operands": 49,
        "number_operators_unique": 5,
        "number_operands_unique": 13,
        "cloc": 21,
        "loc": 64,
        "lloc": 43,
        "mi": 85.38,
        "mIwoC": 46.61,
        "commentWeight": 38.77,
        "kanDefect": 0.45,
        "relativeStructuralComplexity": 121,
        "relativeDataComplexity": 0.35,
        "relativeSystemComplexity": 121.35,
        "totalStructuralComplexity": 484,
        "totalDataComplexity": 1.42,
        "totalSystemComplexity": 485.42,
        "package": "Snicco\\Bridge\\Blade\\",
        "pageRank": 0,
        "afferentCoupling": 1,
        "efferentCoupling": 7,
        "instability": 0.88,
        "violations": {}
    }
]