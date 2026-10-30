# IranYekan font files

Upload your licensed IranYekan WOFF2 files to this folder with these exact names:

- `IRANYekanWebRegular.woff2` — regular weight (400)
- `IRANYekanWebBold.woff2` — bold weight (700)

The CSS font family is `IRANYekan`. The browser will synthesize intermediate weights when needed. Once the files exist, the font is applied to the public website, customer menu, login form, and admin dashboard. Until then, the UI uses Tahoma/system fallbacks.

If your package uses different filenames or has only a variable WOFF2 file, change the paths in `resources/css/fonts.css` to match those files.
