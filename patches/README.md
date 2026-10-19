# Custom renexmoe script patch

The live site uses a custom script outside this repository:
`https://www.daxiaamu.com/wp-content/uploads/files/app@493df0b3.930b7384370c541dd4ba.js`.

`renexmoe-pjax-null-dom.patch` guards missing disk-menu nodes during page initialization. Apply it to that exact script using `git apply --check` and then `git apply` in a directory containing the script. Back up the original file before deploying. Purge that script URL from the CDN and verify directory navigation in the browser. Updating OneManager alone does not deploy this patch.

## Backend diagnosis

Updated OneManager responses include `Server-Timing`:

- `drive`: total time in `get_content()` drive lookups, including cache lookup work.
- `http`: total time in the shared HTTP helper, including Graph, file downloads and global decorations.

Each metric includes a call count. These durations overlap: do not add them together. They contain no file paths or upstream URLs. Inspect HTML responses in browser Network tools, comparing the first and repeat visits. The header does not by itself identify whether the drive cache hit.

Confirmed missing decoration files in paginated folders are cached for 60 seconds. A newly created file outside the current page may take up to 60 seconds to appear. Authentication failures and upstream errors are not negative-cached.

## Ready-to-use supplied theme

`theme/renexmoe-daxiaamu.html` preserves the user-supplied theme and replaces only the failing custom script URL with the fixed copy in this repository, pinned to commit `c24786f28e9d846dfe7ee9c1d5846ade33a70816` via jsDelivr. Select this theme after deploying the updated OneManager code. If `customTheme` is configured, update or clear that override so the selected local theme is used. Existing themes are unchanged.

The fixed script was retrieved from the site URL above; only the two disk-menu DOM guards were changed. `tests/renexmoe-menu.cjs` checks both missing and present menu nodes. This does not replace a browser navigation test after deployment, and the timing changes do not claim to eliminate cold upstream latency.
