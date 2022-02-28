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
        "wmc": 18,
        "ccn": 10,
        "ccnMethodMax": 6,
        "externals": [
            "Snicco\\Component\\Templating\\ViewComposer\\ViewComposerCollection",
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
            "RuntimeException",
            "Snicco\\Bridge\\Blade\\BladeView",
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
        "length": 132,
        "vocabulary": 38,
        "volume": 692.73,
        "difficulty": 9.28,
        "effort": 6429.37,
        "level": 0.11,
        "bugs": 0.23,
        "time": 357,
        "intelligentContent": 74.64,
        "number_operators": 33,
        "number_operands": 99,
        "number_operators_unique": 6,
        "number_operands_unique": 32,
        "cloc": 31,
        "loc": 140,
        "lloc": 109,
        "mi": 67.63,
        "mIwoC": 34.32,
        "commentWeight": 33.31,
        "kanDefect": 0.5,
        "relativeStructuralComplexity": 400,
        "relativeDataComplexity": 0.45,
        "relativeSystemComplexity": 400.45,
        "totalStructuralComplexity": 3600,
        "totalDataComplexity": 4.05,
        "totalSystemComplexity": 3604.05,
        "package": "Snicco\\Bridge\\Blade\\",
        "pageRank": 0,
        "afferentCoupling": 1,
        "efferentCoupling": 14,
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
                "name": "setEngine",
                "role": "setter",
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
        "nbMethods": 1,
        "nbMethodsPrivate": 1,
        "nbMethodsPublic": 0,
        "nbMethodsGetter": 0,
        "nbMethodsSetters": 1,
        "wmc": 2,
        "ccn": 1,
        "ccnMethodMax": 1,
        "externals": [
            "Illuminate\\View\\Component",
            "Snicco\\Bridge\\Blade\\BladeViewFactory",
            "Snicco\\Bridge\\Blade\\BladeView"
        ],
        "parents": [
            "Illuminate\\View\\Component"
        ],
        "lcom": 1,
        "length": 15,
        "vocabulary": 8,
        "volume": 45,
        "difficulty": 3.3,
        "effort": 148.5,
        "level": 0.3,
        "bugs": 0.02,
        "time": 8,
        "intelligentContent": 13.64,
        "number_operators": 4,
        "number_operands": 11,
        "number_operators_unique": 3,
        "number_operands_unique": 5,
        "cloc": 0,
        "loc": 14,
        "lloc": 14,
        "mi": 63.29,
        "mIwoC": 63.29,
        "commentWeight": 0,
        "kanDefect": 0.15,
        "relativeStructuralComplexity": 1,
        "relativeDataComplexity": 1,
        "relativeSystemComplexity": 2,
        "totalStructuralComplexity": 2,
        "totalDataComplexity": 2,
        "totalSystemComplexity": 4,
        "package": "Snicco\\Bridge\\Blade\\",
        "pageRank": 0,
        "afferentCoupling": 0,
        "efferentCoupling": 3,
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
                "role": "setter",
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "name",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "getData",
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
                "name": "render",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "addContext",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "context",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "path",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "withContext",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            }
        ],
        "nbMethodsIncludingGettersSetters": 9,
        "nbMethods": 8,
        "nbMethodsPrivate": 0,
        "nbMethodsPublic": 8,
        "nbMethodsGetter": 0,
        "nbMethodsSetters": 1,
        "wmc": 10,
        "ccn": 2,
        "ccnMethodMax": 2,
        "externals": [
            "Snicco\\Component\\Templating\\View\\View",
            "Illuminate\\Contracts\\View\\View",
            "Illuminate\\View\\View",
            "Snicco\\Component\\Templating\\Exception\\ViewCantBeRendered",
            "Illuminate\\View\\View"
        ],
        "parents": [],
        "lcom": 1,
        "length": 48,
        "vocabulary": 14,
        "volume": 182.75,
        "difficulty": 5.18,
        "effort": 946.99,
        "level": 0.19,
        "bugs": 0.06,
        "time": 53,
        "intelligentContent": 35.27,
        "number_operators": 10,
        "number_operands": 38,
        "number_operators_unique": 3,
        "number_operands_unique": 11,
        "cloc": 4,
        "loc": 52,
        "lloc": 48,
        "mi": 68.05,
        "mIwoC": 47.22,
        "commentWeight": 20.83,
        "kanDefect": 0.15,
        "relativeStructuralComplexity": 100,
        "relativeDataComplexity": 0.61,
        "relativeSystemComplexity": 100.61,
        "totalStructuralComplexity": 900,
        "totalDataComplexity": 5.45,
        "totalSystemComplexity": 905.45,
        "package": "Snicco\\Bridge\\Blade\\",
        "pageRank": 0,
        "afferentCoupling": 3,
        "efferentCoupling": 4,
        "instability": 0.57,
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
        "cloc": 15,
        "loc": 52,
        "lloc": 37,
        "mi": 86.1,
        "mIwoC": 49.13,
        "commentWeight": 36.97,
        "kanDefect": 0.52,
        "relativeStructuralComplexity": 121,
        "relativeDataComplexity": 0.52,
        "relativeSystemComplexity": 121.52,
        "totalStructuralComplexity": 484,
        "totalDataComplexity": 2.08,
        "totalSystemComplexity": 486.08,
        "package": "Snicco\\Bridge\\Blade\\",
        "pageRank": 0,
        "afferentCoupling": 2,
        "efferentCoupling": 6,
        "instability": 0.75,
        "violations": {}
    }
]