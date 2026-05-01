const Encore = require("@symfony/webpack-encore");

if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || "dev");
}

Encore.setOutputPath("public/build/app/")
    .setPublicPath("/build/app")

    .addEntry("app", "./assets/app/app.js")

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

module.exports = Encore.getWebpackConfig();
