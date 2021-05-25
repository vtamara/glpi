/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

const webpack = require('webpack');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

const glob = require('glob');
const path = require('path');

const libOutputPath = 'public/lib';
const scssOutputPath = 'css/lib';

/*
 * GLPI core files build configuration.
 */
var glpiConfig = {
   entry: {
      'glpi': path.resolve(__dirname, 'js/main.js'),
   },
   output: {
      path: path.resolve(__dirname, 'public/build'),
   },
};

/*
 * External libraries files build configuration.
 */
var libsConfig = {
   entry: function () {
      // Create an entry per *.js file in lib/bundle directory.
      // Entry name will be name of the file (without ext).
      var entries = {};

      let files = glob.sync(path.resolve(__dirname, 'lib/bundles') + '/!(*.min).js');
      files.forEach(function (file) {
         entries[path.basename(file, '.js')] = file;
      });

      return entries;
   },
   output: {
      path: path.resolve(__dirname, libOutputPath),
      publicPath: '', // keep URLs relative to output path
   },
   module: {
      rules: [
         {
            // Load scripts with no compilation for packages that are directly providing "dist" files.
            // This prevents useless compilation pass and can also
            // prevents incompatibility issues with the webpack require feature.
            // It also removes existing sourcemaps that cannot be used correctly.
            test: /\.js$/,
            include: [
               path.resolve(__dirname, 'node_modules/@fullcalendar'),
               path.resolve(__dirname, 'node_modules/@fullcalendar'),
               path.resolve(__dirname, 'node_modules/codemirror'),
               path.resolve(__dirname, 'node_modules/cystoscape'),
               path.resolve(__dirname, 'node_modules/cytoscape-context-menus'),
               path.resolve(__dirname, 'node_modules/gridstack'),
               path.resolve(__dirname, 'node_modules/jquery-migrate'),
               path.resolve(__dirname, 'node_modules/jstree'),
               path.resolve(__dirname, 'node_modules/photoswipe'),
               path.resolve(__dirname, 'node_modules/rrule'),
               path.resolve(__dirname, 'vendor/blueimp/jquery-file-upload'),
            ],
            use: ['script-loader', 'strip-sourcemap-loader'],
         },
         {
            // Build styles
            test: /\.css$/,
            use: [MiniCssExtractPlugin.loader, 'css-loader'],
         },
         {
            // Copy images and fonts
            test: /\.((gif|png|jp(e?)g)|(eot|ttf|svg|woff2?))$/,
            use: {
               loader: 'file-loader',
               options: {
                  name: function (filename) {
                     // Keep only relative path
                     var sanitizedPath = path.relative(__dirname, filename);

                     // Sanitize name
                     sanitizedPath = sanitizedPath.replace(/[^\\/\w-.]/, '');

                     // Remove the first directory (lib, node_modules, ...) and empty parts
                     // and replace directory separator by '/' (windows case)
                     sanitizedPath = sanitizedPath.split(path.sep)
                        .filter(function (part, index) {
                           return '' != part && index != 0;
                        }).join('/');

                     return sanitizedPath;
                  },
               },
            },
         },
      ],
   },
   plugins: [
      new webpack.ProvidePlugin(
         {
            process: 'process/browser', // required in util.js (indirect dependency of file-type.js)
            Buffer: ['buffer', 'Buffer'], // required in file-type.js
         }
      ),
      new CleanWebpackPlugin(
         {
            cleanOnceBeforeBuildPatterns: [
               path.join(process.cwd(), libOutputPath + '/**/*'),
               path.join(process.cwd(), scssOutputPath + '/**/*')
            ]
         }
      ), // Clean lib dir content
      new MiniCssExtractPlugin(), // Extract styles into CSS files
   ],
   resolve: {
      // Use only main file in requirement resolution as we do not yet handle modules correctly
      mainFields: [
         'main',
      ],
      alias: {
         'stream': 'stream-browserify',
      },
   },
};

// Copy raw JS and SCSS files
var filesToCopy = [
   // JS files
   {
      package: '@fullcalendar/core',
      from: 'locales/*.js',
   },
   {
      package: 'flatpickr',
      context: 'dist',
      from: 'l10n/*.js',
   },
   {
      package: 'flatpickr',
      context: 'dist',
      from: 'themes/*.css',
   },
   {
      package: 'jquery-ui',
      context: 'ui',
      from: 'i18n/*.js',
   },
   {
      package: 'select2',
      context: 'dist',
      from: 'js/i18n/*.js',
   },
   {
      package: 'tinymce',
      from: 'skins/**/*',
   },
   {
      package: 'tinymce-i18n',
      from: 'langs/*.js',
   },
   // SCSS files
   {
      package: '@tabler/core',
      from: 'src/scss/**/*.scss',
      to: scssOutputPath,
   },
   {
      package: 'bootstrap',
      from: 'scss/**/*.scss',
      to: scssOutputPath,
   },
   {
      package: 'select2',
      from: 'src/scss/**/*.scss',
      to: scssOutputPath,
   }
];

let copyPatterns = [];
for (let s = 0; s < filesToCopy.length; s++) {
   let specs = filesToCopy[s];
   let to = (specs.to || libOutputPath) + '/' + specs.package.replace(/^@/, ''); // remove leading @ in case of prefixed package

   let context = 'node_modules/' + specs.package;
   if (Object.prototype.hasOwnProperty.call(specs, 'context')) {
      context += '/' + specs.context;
   }

   let copyParams = {
      context: path.resolve(__dirname, context),
      from:    specs.from,
      to:      path.resolve(__dirname, to),
      toType:  'dir',
   };

   if (Object.prototype.hasOwnProperty.call(specs, 'ignore')) {
      copyParams.ignore = specs.ignore;
   }

   copyPatterns.push(copyParams);
}

// Replace jstree images
copyPatterns.push(
   {
      context: path.resolve(__dirname, 'lib/jstree/themes/glpi'),
      force:   true,
      from:    '*.*',
      to:      path.resolve(__dirname, libOutputPath + '/jstree/dist/themes/default'),
      toType:  'dir',
   }
);

libsConfig.plugins.push(new CopyWebpackPlugin({patterns:copyPatterns}));

module.exports = function() {
   var configs = [glpiConfig, libsConfig];

   for (let config of configs) {
      config.mode = 'none'; // Force 'none' mode, as optimizations will be done on release process
      config.devtool = 'source-map'; // Add sourcemap to files

      // Limit verbosity to only usefull information
      config.stats = {
         all: false,
         errors: true,
         errorDetails: true,
         warnings: true,

         entrypoints: true,
         timings: true,
      };
   }

   return configs;
};
