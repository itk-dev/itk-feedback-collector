const Encore = require("@symfony/webpack-encore");

if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || "dev");
}

Encore.setOutputPath("public/build/widget/")
    .setPublicPath("/build/widget")

    .addEntry("widget", "./assets/widget/widget.js")

    .disableSingleRuntimeChunk()

    .cleanupOutputBeforeBuild()
    .enableSourceMaps(!Encore.isProduction())

    .configureBabel((config) => {
        config.plugins.push("@babel/plugin-transform-class-properties");
    })

    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = "usage";
        config.corejs = 3;
    });

const config = Encore.getWebpackConfig();

// Replace CSS loaders with raw css-loader so imports return CSS strings.
// This is needed because the widget injects CSS into Shadow DOM manually.
config.module.rules = config.module.rules.map((rule) => {
    if (rule.oneOf) {
        rule.oneOf = rule.oneOf.map((oneOfRule) => {
            if (oneOfRule.use) {
                const hasCssLoader = oneOfRule.use.some(
                    (loader) =>
                        (typeof loader === "string" &&
                            loader.includes("css-loader")) ||
                        (typeof loader === "object" &&
                            loader.loader &&
                            loader.loader.includes("css-loader")),
                );
                if (hasCssLoader) {
                    // Use only css-loader with exportType string
                    oneOfRule.use = [
                        {
                            loader: require.resolve("css-loader"),
                            options: { exportType: "string" },
                        },
                    ];
                }
            }
            return oneOfRule;
        });
    }
    return rule;
});

// Remove MiniCssExtractPlugin since we're not extracting CSS
config.plugins = config.plugins.filter(
    (plugin) => plugin.constructor.name !== "MiniCssExtractPlugin",
);

module.exports = config;
