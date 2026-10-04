# Decision: no build system for now

Current decision: keep the website as directly editable static HTML/CSS/JS.

Do not introduce Eleventy/11ty, Astro, React, Vite, Node dependencies, package.json workflows or any build step unless the owner explicitly requests it later.

Reason:
- The owner is not a web developer.
- The site should remain easy to open, inspect, edit and upload.
- The current PHP usage is limited to isolated form handling in `/backend/`.
- AI assistance should work on the actual deployed files, not on a separate source/build-output model.

Future option:
A build system may be reconsidered only if header/footer maintenance becomes too painful or the site grows significantly. If that happens, create a separate prototype first and do not replace the working website directly.
