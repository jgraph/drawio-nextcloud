# Manual Test Checklist

Run through these tests after making changes. Start the dev environment with `./scripts/dev-setup.sh` and open http://localhost:8088 (admin / admin).

## 1. File Creation

- [ ] In Files app, click "+" > "New draw.io Diagram" appears
- [ ] Click it > new `.drawio` file created, editor opens
- [ ] In Files app, click "+" > "New draw.io Whiteboard" appears
- [ ] Click it > new `.dwb` file created, editor opens
- [ ] "New draw.io Diagram" appears in Favorites view (not just All Files)
- [ ] "New draw.io Diagram" appears after a full page reload (not just after navigation)

## 2. Editing

- [ ] Open an existing `.drawio` file > draw.io editor loads in iframe
- [ ] Draw something, wait for autosave > status bar shows "Autosaved at [time]"
- [ ] Manual save with Ctrl+S / File > Save > status bar shows "Saved at [time]"
- [ ] Close editor > returns to Files app, file size updated
- [ ] Reopen the file > previous edits preserved

## 3. Template Selection

- [ ] Create a new `.drawio` file > draw.io template picker dialog appears
- [ ] Select a template > diagram loads with template content
- [ ] Create a new `.dwb` whiteboard > no template picker, starts with empty canvas

## 4. Preview Thumbnails

- [ ] With previews enabled in admin settings: edit a diagram, close > preview thumbnail visible in Files list
- [ ] Delete a `.drawio` file > no orphaned preview PNG left behind
- [ ] With previews disabled in admin settings: edit and close > no thumbnail generated

## 5. Version History

- [ ] Edit and save a file multiple times
- [ ] In draw.io, check if version history is accessible (File > Revision History)
- [ ] Load a previous version > old content displayed
- [ ] Verify versions work when Files Versions app is enabled
- [ ] Verify graceful fallback when Files Versions app is disabled

## 6. Public Sharing (Read-Write)

- [ ] Share a `.drawio` file via public link with edit permission
- [ ] Open the public link in an incognito window > editor loads with toolbar
- [ ] Make edits and save > changes persist
- [ ] Close and reopen via the share link > edits preserved

## 7. Public Sharing (Read-Only)

- [ ] Share a `.drawio` file via public link with view-only permission
- [ ] Open in incognito > editor loads in viewer mode (no toolbar/chrome)
- [ ] Verify no save/autosave occurs

## 8. Public Sharing (Password-Protected)

- [ ] Share a `.drawio` file with a password
- [ ] Open share link in incognito > password prompt appears
- [ ] Enter correct password > editor loads

## 9. Concurrency / Conflict Detection

- [ ] Open the same file in two browser tabs/windows
- [ ] Save in one tab
- [ ] Save in the other tab > conflict error message appears ("The file you are working on was updated in the meantime")
- [ ] Error message suggests using Export to save changes

## 10. File Locking

- [ ] Verify file locking during save (exclusive lock acquired/released)
- [ ] Verify file locking during load (shared lock acquired/released)
- [ ] If a file is locked by another process > "The file is locked" error shown

## 11. Large File Protection

- [ ] Attempt to open a file larger than 100MB > error message shown ("This file is too big to be opened")

## 12. MIME Type Integration

- [ ] `.drawio` files show the orange draw.io icon in the file list
- [ ] `.dwb` files show the whiteboard icon in the file list
- [ ] Icons persist after a Nextcloud upgrade (relates to #119)

## 13. Admin Settings

Navigate to Settings > Administration > Draw.io:

- [ ] All form fields render (URL, theme, dark mode, language, offline, autosave, libraries, previews, config textarea)
- [ ] Change settings, click Save > success toast appears
- [ ] Settings persist after page reload

### Individual Settings

- [ ] **Draw.io URL** - change to a custom/self-hosted URL > editor loads from that URL
- [ ] **Theme** - change theme (Kennedy, Minimal, Sketch, Atlas, Dark) > editor uses selected theme
- [ ] **Dark mode on** - editor loads with `&dark=1`
- [ ] **Dark mode auto** - detects Nextcloud dark theme preference
- [ ] **Dark mode off** - editor loads in light mode
- [ ] **Language auto** - editor language follows Nextcloud user language setting
- [ ] **Language explicit** - override to a specific language > editor uses that language
- [ ] **Offline mode** - enable > editor loads with `&offline=1&stealth=1`
- [ ] **Autosave toggle** - disable > no autosave occurs; enable > autosave resumes
- [ ] **Libraries** - enable > shape libraries panel visible in editor
- [ ] **Previews toggle** - disable > no PNG preview generated on close
- [ ] **Custom config JSON** - enter valid JSON > passed to draw.io `configure` event
- [ ] **Custom config JSON** - enter invalid JSON > error toast shown, settings not saved

## 14. Translations

- [ ] Change Nextcloud user language to German (Settings > Personal > Language)
- [ ] Admin settings page shows translated strings
- [ ] File menu entries show translated text ("Neues draw.io Diagramm")
- [ ] Editor loading message is translated

## 15. Dark Mode Integration

- [ ] With Nextcloud dark theme enabled and dark mode set to "auto" > draw.io loads in dark mode
- [ ] With Nextcloud light theme and dark mode "auto" > draw.io loads in light mode
- [ ] System `prefers-color-scheme: dark` detected when no NC theme is explicitly set

## 16. Error Handling

- [ ] Open a file that has been deleted mid-session > appropriate error shown
- [ ] Network interruption during save > error message with "Use Export to save changes"
- [ ] Invalid fileId in URL > error displayed
- [ ] Unauthenticated access to editor page > redirect to login
