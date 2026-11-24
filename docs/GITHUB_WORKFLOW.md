# GitHub Version-Control Workflow (BIT 2103)

This document records the version-control process for the Banya Hostels website. It supports the Task 5 requirement to maintain a GitHub repository containing custom code, a README and documentation, with at least eight meaningful commits and one feature branch merged by pull request.

## Repository conventions

- `main` is the stable branch.
- Create a short-lived `feature/...` branch for a specific improvement.
- Make commits that describe actual changes; do not create empty or misleading commits just to increase the count.
- Push local commits to GitHub.
- Open a pull request from the feature branch into `main`.
- Review the changed files and test the website before merging.
- Keep credentials, private configuration, database dumps containing real records and uploaded personal data out of the repository.

## Suggested commit plan

Use these as a plan, not as claims that the work has already been completed. Make each commit only after completing and testing the corresponding change. If the repository already has an initial commit, count it and adapt the remaining plan.

| Order | Suggested commit message | Work that should be completed before committing |
|---|---|---|
| 1 | `Add Banya Hostels website source code` | Add the existing custom PHP, CSS, JavaScript, image and database-schema files after checking for secrets and private data. |
| 2 | `Document project setup and architecture` | Add or update `README.md` with the actual stack, structure, local setup and deployment notes. |
| 3 | `Improve page metadata and heading structure` | Review page-specific titles, meta descriptions and heading hierarchy; test the rendered source. |
| 4 | `Update sitemap and crawler instructions` | Confirm sitemap URLs and `robots.txt` use the real production domain; validate the XML response. |
| 5 | `Add keyword-focused accommodation article` | Publish and proofread a genuine article of at least 500 words in the website's news/blog system; verify it appears publicly. |
| 6 | `Optimize hostel images for web delivery` | Compress suitable images, update references where filenames/formats change, and check image quality and broken links. |
| 7 | `Improve browser caching configuration` | Update and test supported caching rules; inspect response headers in browser developer tools. |
| 8 | `Document testing and deployment procedures` | Add verified testing/deployment notes and update the checklist with actual outcomes. |

Additional commits may cover genuine bug fixes, responsive layout improvements, accessibility, validation or security improvements.

## Example feature-branch workflow

The following example uses a homepage accessibility improvement. Use it only if you actually make and test the change.

1. Update your local `main` branch so it matches the latest remote version.
2. In GitHub Desktop, select **Current Branch → New Branch**.
3. Name the branch `feature/homepage-accessibility`.
4. Make a specific improvement, such as adding meaningful alternative text to informative homepage images.
5. Save the files and review the changed-file list in GitHub Desktop.
6. Enter a descriptive commit message, for example: `Add descriptive alt text to homepage images`.
7. Commit the change on `feature/homepage-accessibility`.
8. Select **Publish branch** (or **Push origin**) to upload it.
9. On GitHub, open a pull request with:
   - **Base:** `main`
   - **Compare:** `feature/homepage-accessibility`
   - **Title:** `Improve homepage image accessibility`
   - **Description:** Explain the actual changes and how they were tested.
10. Review the diff and check that no secrets or unrelated files are included.
11. Merge the pull request after the checks and review are complete.
12. In GitHub Desktop, switch back to `main` and pull the latest changes.
13. Confirm the merged change is present and the website still works.

## Example pull-request description

Use and edit this only to match the changes actually made:

```text
## Summary
- Describe the homepage accessibility change.
- Identify the files changed.

## Testing
- Opened the homepage locally.
- Checked that the affected images load.
- Checked the rendered HTML for the expected alt attributes.

## Notes
- No credentials or private customer data are included.
```

## Evidence to retain for assessment

Save dated screenshots showing:
- The GitHub repository page and its file list.
- The commit history showing at least eight meaningful commits.
- The feature branch in GitHub.
- The pull request showing the correct base and compare branches.
- The merged pull request and merge status.

The assignment asks for a repository with custom code, README and documentation, at least eight meaningful commits, and one feature branch merged by pull request. Keep the repository public or explicitly grant the examiner access, as required by the assignment. Do not publish client-confidential information merely to make the repository public.
