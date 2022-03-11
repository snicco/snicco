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
        "wmc": 16,
        "ccn": 8,
        "ccnMethodMax": 6,
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
        "lcom": 2,
        "length": 115,
        "vocabulary": 33,
        "volume": 580.11,
        "difficulty": 7.59,
        "effort": 4402.59,
        "level": 0.13,
        "bugs": 0.19,
        "time": 245,
        "intelligentContent": 76.44,
        "number_operators": 30,
        "number_operands": 85,
        "number_operators_unique": 5,
        "number_operands_unique": 28,
        "cloc": 30,
        "loc": 130,
        "lloc": 100,
        "mi": 69.82,
        "mIwoC": 35.95,
        "commentWeight": 33.87,
        "kanDefect": 0.43,
        "relativeStructuralComplexity": 289,
        "relativeDataComplexity": 0.52,
        "relativeSystemComplexity": 289.52,
        "totalStructuralComplexity": 2601,
        "totalDataComplexity": 4.72,
        "totalSystemComplexity": 2605.72,
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
                "name": "view",
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
        "lcom": 2,
        "length": 10,
        "vocabulary": 6,
        "volume": 25.85,
        "difficulty": 2,
        "effort": 51.7,
        "level": 0.5,
        "bugs": 0.01,
        "time": 3,
        "intelligentContent": 12.92,
        "number_operators": 2,
        "number_operands": 8,
        "number_operators_unique": 2,
        "number_operands_unique": 4,
        "cloc": 4,
        "loc": 14,
        "lloc": 10,
        "mi": 104.99,
        "mIwoC": 68.16,
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
        "name": "Snicco\\Bridge\\Blade\\BladeView",
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
                "name": "path",
                "role": "getter",
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "cloneView",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            }
        ],
        "nbMethodsIncludingGettersSetters": 7,
        "nbMethods": 4,
        "nbMethodsPrivate": 1,
        "nbMethodsPublic": 3,
        "nbMethodsGetter": 3,
        "nbMethodsSetters": 0,
        "wmc": 10,
        "ccn": 4,
        "ccnMethodMax": 3,
        "externals": [
            "Snicco\\Component\\Templating\\View\\View",
            "Illuminate\\View\\View",
            "Snicco\\Component\\Templating\\Exception\\ViewCantBeRendered",
            "Snicco\\Component\\Templating\\View\\View",
            "Illuminate\\View\\View",
            "Illuminate\\View\\View"
        ],
        "parents": [],
        "lcom": 2,
        "length": 60,
        "vocabulary": 15,
        "volume": 234.41,
        "difficulty": 5.63,
        "effort": 1318.58,
        "level": 0.18,
        "bugs": 0.08,
        "time": 73,
        "intelligentContent": 41.67,
        "number_operators": 15,
        "number_operands": 45,
        "number_operators_unique": 3,
        "number_operands_unique": 12,
        "cloc": 27,
        "loc": 76,
        "lloc": 49,
        "mi": 85.88,
        "mIwoC": 46,
        "commentWeight": 39.88,
        "kanDefect": 0.38,
        "relativeStructuralComplexity": 121,
        "relativeDataComplexity": 0.54,
        "relativeSystemComplexity": 121.54,
        "totalStructuralComplexity": 847,
        "totalDataComplexity": 3.75,
        "totalSystemComplexity": 850.75,
        "package": "Snicco\\Bridge\\Blade\\",
        "pageRank": 0,
        "afferentCoupling": 2,
        "efferentCoupling": 3,
        "instability": 0.6,
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
        "wmc": 4,
        "ccn": 3,
        "ccnMethodMax": 3,
        "externals": [
            "Snicco\\Component\\Templating\\ViewComposer\\ViewComposerCollection",
            "RuntimeException",
            "Snicco\\Bridge\\Blade\\BladeView"
        ],
        "parents": [],
        "lcom": 1,
        "length": 23,
        "vocabulary": 11,
        "volume": 79.57,
        "difficulty": 3.38,
        "effort": 268.54,
        "level": 0.3,
        "bugs": 0.03,
        "time": 15,
        "intelligentContent": 23.58,
        "number_operators": 5,
        "number_operands": 18,
        "number_operators_unique": 3,
        "number_operands_unique": 8,
        "cloc": 0,
        "loc": 18,
        "lloc": 18,
        "mi": 58.9,
        "mIwoC": 58.9,
        "commentWeight": 0,
        "kanDefect": 0.22,
        "relativeStructuralComplexity": 9,
        "relativeDataComplexity": 0.38,
        "relativeSystemComplexity": 9.38,
        "totalStructuralComplexity": 18,
        "totalDataComplexity": 0.75,
        "totalSystemComplexity": 18.75,
        "package": "Snicco\\Bridge\\Blade\\",
        "pageRank": 0,
        "afferentCoupling": 1,
        "efferentCoupling": 3,
        "instability": 0.75,
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
                "name": "normalizeNames",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "convertAbsolutePathToName",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            }
        ],
        "nbMethodsIncludingGettersSetters": 4,
        "nbMethods": 4,
        "nbMethodsPrivate": 2,
        "nbMethodsPublic": 2,
        "nbMethodsGetter": 0,
        "nbMethodsSetters": 0,
        "wmc": 8,
        "ccn": 5,
        "ccnMethodMax": 3,
        "externals": [
            "Snicco\\Component\\Templating\\ViewFactory\\ViewFactory",
            "Illuminate\\View\\Factory",
            "Snicco\\Bridge\\Blade\\BladeView",
            "Snicco\\Bridge\\Blade\\BladeView",
            "Snicco\\Component\\Templating\\Exception\\ViewNotFound",
            "Illuminate\\View\\ViewName",
            "Illuminate\\Support\\Str",
            "Illuminate\\Support\\Str"
        ],
        "parents": [],
        "lcom": 1,
        "length": 48,
        "vocabulary": 16,
        "volume": 192,
        "difficulty": 6,
        "effort": 1152,
        "level": 0.17,
        "bugs": 0.06,
        "time": 64,
        "intelligentContent": 32,
        "number_operators": 12,
        "number_operands": 36,
        "number_operators_unique": 4,
        "number_operands_unique": 12,
        "cloc": 16,
        "loc": 53,
        "lloc": 37,
        "mi": 86.73,
        "mIwoC": 49.13,
        "commentWeight": 37.6,
        "kanDefect": 0.52,
        "relativeStructuralComplexity": 121,
        "relativeDataComplexity": 0.52,
        "relativeSystemComplexity": 121.52,
        "totalStructuralComplexity": 484,
        "totalDataComplexity": 2.08,
        "totalSystemComplexity": 486.08,
        "package": "Snicco\\Bridge\\Blade\\",
        "pageRank": 0,
        "afferentCoupling": 1,
        "efferentCoupling": 6,
        "instability": 0.86,
        "violations": {}
    }
]