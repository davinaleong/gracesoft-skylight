# Manual Test Checklist

## Authentication

- [ ] Register a new account with valid email and password
- [ ] Register with an already-used email shows a validation error
- [ ] Log in with correct credentials redirects to `/home`
- [ ] Log in with wrong password shows an error
- [ ] Remember me keeps the session across browser restart
- [ ] Log out clears the session and redirects to landing
- [ ] Forgot password form sends reset email
- [ ] Password reset link works and updates password
- [ ] Password reset link cannot be reused
- [ ] Email verification banner appears after registration
- [ ] Resend verification email button sends the email
- [ ] Verified user is no longer shown the verification banner
- [ ] OAuth (Google/GitHub) login creates a new account on first use
- [ ] OAuth login on a returning account signs in without creating a duplicate

## Two-Factor Authentication (2FA)

- [ ] Enable 2FA from profile shows QR code and setup key
- [ ] Confirming 2FA with a valid TOTP code activates it
- [ ] Login with 2FA enabled prompts for a TOTP code
- [ ] Entering an invalid TOTP code shows an error
- [ ] Login using a recovery code works
- [ ] Recovery codes can be regenerated from profile
- [ ] Disabling 2FA requires password confirmation

## Profile & Account

- [ ] Update name and email from profile page
- [ ] Change password — old password must be correct
- [ ] Profile page shows current 2FA status
- [ ] Password confirmation gate appears before sensitive actions

## Boards

- [ ] Home dashboard lists all boards for the current user
- [ ] Create board with a name and optional description
- [ ] Create board from a template (Kanban, Scrum, etc.)
- [ ] Board appears on the home dashboard after creation
- [ ] Board count badge reflects the plan limit correctly
- [ ] Opening a board navigates to its detail page
- [ ] Delete board from the home dashboard (with confirmation)
- [ ] Accessing another user's board returns 403
- [ ] Board name and description are truncated correctly in the grid

## Columns & Cards

- [ ] Add a column to a board
- [ ] Column name is required — empty submit shows an error
- [ ] Delete a column (with confirmation)
- [ ] Add a card to a column
- [ ] Card title is required — empty submit shows an error
- [ ] Edit a card's title, description, start date, due date, and colour
- [ ] Save card updates it in place without a page reload
- [ ] Delete a card (with confirmation)
- [ ] Drag a card to a different column — position persists after page reload
- [ ] Drag to reorder cards within a column — order persists after reload
- [ ] Drag to reorder columns — order persists after reload

## Card Detail Panel

- [ ] Opening a card slide-over shows all fields
- [ ] Markdown in the description renders correctly
- [ ] Add a checklist and checklist items
- [ ] Toggle a checklist item as complete / incomplete
- [ ] Delete a checklist item
- [ ] Add a comment on a card
- [ ] Delete own comment
- [ ] Upload an attachment to a card
- [ ] Download an attachment
- [ ] Delete an attachment
- [ ] Add a markdown note to a card
- [ ] Assign and remove labels on a card
- [ ] Card colour renders in the kanban column tile

## Labels

- [ ] Create a label (name + colour picker) from the board label manager
- [ ] Label appears in the label list on the board
- [ ] Delete a label removes it from all cards that had it
- [ ] Filter board by one or more labels shows only matching cards

## Board Filters

- [ ] Filter by label shows only matching cards
- [ ] Filter by column shows only cards in that column
- [ ] Filter by due date (overdue / due today / upcoming)
- [ ] Clearing filters restores all cards

## Board Sharing (Client Portal)

- [ ] Generate a share link with default options (no comments, no attachments, no expiry)
- [ ] The raw token is shown once and contains a copy button
- [ ] Share link with "show comments" includes comments in the viewer
- [ ] Share link with "show attachments" includes attachments in the viewer
- [ ] Share link with a 7 / 30 / 90-day expiry shows the correct expiry label
- [ ] An expired link returns 404
- [ ] Revoking an active link returns 404 when accessed
- [ ] Revoked link shows "Revoked" badge in the panel
- [ ] Share link access count increments on each visit

## Read-Only Board Viewer (`/view/{token}`)

- [ ] All columns and cards render correctly
- [ ] Clicking a card opens the read-only detail modal
- [ ] Card description renders as markdown
- [ ] Checklists display completion state (no toggle interaction)
- [ ] No edit, drag, or delete controls are visible
- [ ] "Client Portal" banner is displayed at the top
- [ ] Page has `noindex` meta tag

## Workspace & Team

- [ ] Team page lists current workspace members
- [ ] Invite a user by email — invite email is sent
- [ ] Accepting an invite adds the user to the workspace
- [ ] Invited user can see shared boards
- [ ] Viewer role cannot create, edit, or delete content
- [ ] Member role can create and edit content
- [ ] Owner can change member roles
- [ ] Owner can remove a member from the workspace

## Billing

- [ ] Billing page loads without errors
- [ ] Current plan and limits are displayed correctly
- [ ] Upgrade link navigates to Stripe Checkout (test mode)
- [ ] Board limit is enforced — creating over the limit shows an error

## Webhooks & Integrations

- [ ] Webhooks page loads and lists configured endpoints
- [ ] Adding a webhook endpoint saves and appears in the list
- [ ] Deleting a webhook endpoint removes it
- [ ] Integrations page loads without errors

## Notifications & Email

- [ ] Card due reminder email is sent for cards due today (run `app:send-card-due-reminders`)
- [ ] Overdue card reminder email is sent correctly
- [ ] Share link created notification email is received
- [ ] Share link revoked confirmation email is received

## Activity Log

- [ ] Creating / editing / deleting a card appears in the activity feed
- [ ] Moving a card between columns is logged
- [ ] Share link created and revoked events appear in the log
- [ ] Activity timestamps are correct

## Board Export

- [ ] Export a board via `GET /boards/{board}/export`
- [ ] Exported file contains all columns and cards
- [ ] Accessing export as a non-member returns 403

## REST API (v1)

- [ ] All API endpoints require a valid Sanctum token
- [ ] `GET /api/v1/boards` returns only the authenticated user's boards
- [ ] `POST /api/v1/boards` creates a board
- [ ] `GET /api/v1/boards/{board}/columns` returns columns
- [ ] `POST /api/v1/boards/{board}/columns` creates a column
- [ ] `POST /api/v1/columns/{column}/cards` creates a card
- [ ] `PATCH /api/v1/cards/{card}` updates a card
- [ ] `DELETE /api/v1/cards/{card}` deletes a card
- [ ] `POST /api/v1/cards/{card}/comments` adds a comment
- [ ] Requests over the API rate limit return 429

## Public Pages

- [ ] Landing page (`/`) redirects authenticated users to `/home`
- [ ] Pricing page loads correctly
- [ ] Changelog page loads correctly
- [ ] Feedback page form submits successfully
- [ ] Status page (`/status`) shows all checks as healthy
- [ ] Security, Privacy, and Terms pages load without errors

## Global Search

- [ ] Searching for a board name returns that board
- [ ] Searching for a card title returns that card
- [ ] Search results are scoped to the current user's boards
- [ ] Empty search returns no results gracefully

## General UI & Responsiveness

- [ ] App renders correctly on mobile (375 px)
- [ ] App renders correctly on tablet (768 px)
- [ ] App renders correctly on desktop (1280 px+)
- [ ] Dark mode toggle works and persists across page loads
- [ ] No console JavaScript errors on any page
- [ ] No broken images or missing assets
- [ ] All flash/success messages disappear after a short delay
- [ ] All confirmation dialogs require explicit confirmation before destructive actions
