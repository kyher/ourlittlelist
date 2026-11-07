# Feature Specification: Couple Management

**Feature Branch**: `couple-management`

**Created**: 2026-11-07

**Status**: Draft

**Input**: User description: "Issue 3 on github. A user not in a couple yet should be able to search for another user's username to request to be in a couple. The target user can then accept or reject. Once accepted, a couple is created between the two users. If rejected, a couple is not created. Couples can be ended. Searching for a user should not bring back your own couple. We should add appropriate validation for those users already in a couple."

GitHub issue #3 ("Add ability to create / end couples with user search"):
- Search for usernames if not in couple
- Can request couple if not in couple
- User can accept if not in couple
- Couples can be ended

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Send a couple request to a found user (Priority: P1)

Kyle is signed in and not in a couple. He searches for his partner's username (`harry`) using the existing partner search, sees Harry in the result, and sends Harry a couple request. Kyle can see that his request is waiting for Harry's answer, and he can cancel it if he changes his mind.

**Why this priority**: No couple can exist until one person asks the other. This turns the existing search (feature 002) into the first step of connecting.

**Independent Test**: With two single accounts, `kyle` and `harry`, sign in as Kyle, search for `harry`, send a request, and confirm Kyle sees it as pending. Cancel it and confirm it is gone.

**Acceptance Scenarios**:

1. **Given** Kyle and Harry are both not in a couple, **When** Kyle searches for `harry` and sends a request, **Then** a pending request from Kyle to Harry is recorded and Kyle sees "Request sent, waiting for Harry".
2. **Given** Kyle has a pending request to Harry, **When** Kyle cancels it, **Then** the request is removed and Harry no longer sees it.
3. **Given** Kyle has a pending request to Harry, **When** Kyle searches for `harry` again, **Then** he sees that a request is already pending instead of an option to send another.
4. **Given** Kyle has a pending request to Harry, **When** Kyle tries to send a request to a third user, **Then** he is told he must cancel his current request first.
5. **Given** Kyle is already in a couple, **When** he opens the partner search, **Then** he cannot search or send requests and is instead shown his current couple.

---

### User Story 2 - Accept or reject a couple request (Priority: P1)

Harry signs in and sees that Kyle has asked to be his partner. Harry can accept, which creates the couple between them, or reject, which leaves both of them single.

**Why this priority**: Consent from both people is required by the constitution (Principle III). Without accept/reject, no couple can be created.

**Independent Test**: With a pending request from Kyle to Harry, sign in as Harry, accept it, and confirm both users are now shown as a couple. Repeat with a fresh request and reject it; confirm no couple exists and Kyle can send a new request.

**Acceptance Scenarios**:

1. **Given** a pending request from Kyle to Harry, **When** Harry signs in, **Then** he sees the incoming request showing Kyle's display name and username.
2. **Given** a pending request from Kyle to Harry, **When** Harry accepts it, **Then** a couple is created containing exactly Kyle and Harry, and both see each other as their partner.
3. **Given** a pending request from Kyle to Harry, **When** Harry rejects it, **Then** no couple is created, the request is closed, and both stay single.
4. **Given** Harry rejected Kyle's request, **When** Kyle next looks at his request status, **Then** he sees it was declined and can send a new request (to Harry or anyone else).
5. **Given** a pending request from Kyle to Harry, **When** any user other than Harry tries to accept or reject it, **Then** the action is refused.
6. **Given** Harry has pending requests from Kyle and from Sam, **When** Harry accepts Kyle's, **Then** Sam's request to Harry is automatically closed, and any request Harry had sent is cancelled.

---

### User Story 3 - End a couple (Priority: P2)

Either partner can end the couple. After a confirmation step, the couple ends for both of them and each becomes free to search for and connect with someone again.

**Why this priority**: Needed so people are not stuck in a couple created by mistake or that no longer applies. It comes after creation because there is nothing to end without stories 1 and 2.

**Independent Test**: With an active couple of Kyle and Harry, sign in as Kyle, end the couple, confirm the prompt, and verify both users are now single and can use partner search again.

**Acceptance Scenarios**:

1. **Given** Kyle and Harry are a couple, **When** Kyle chooses to end the couple, **Then** he is asked to confirm before anything changes, and the confirmation warns that the couple's reviews will be permanently deleted.
2. **Given** Kyle is shown the end-couple confirmation, **When** he cancels it, **Then** the couple and its data are unchanged.
3. **Given** Kyle confirms ending the couple, **When** the action completes, **Then** neither Kyle nor Harry is in a couple, the couple's data is deleted, and both can search for and request partners again.
4. **Given** Kyle ended the couple, **When** Harry next signs in, **Then** he sees that he is no longer in a couple.
5. **Given** Kyle and Harry are a couple, **When** a user outside the couple tries to end it, **Then** the action is refused.

---

### Edge Cases

- Kyle and Harry send requests to each other at about the same time: the second request is not created as a duplicate. Instead the second sender is shown the incoming request from the other person and can accept it.
- Harry accepts Kyle's request, but Kyle joined another couple in the meantime (or Harry did): the accept fails with a clear message, and no couple is created.
- Two people accept requests involving the same user at the same moment: at most one couple is created for that user.
- A request is still pending when its sender cancels it and the receiver tries to accept: the accept fails and the receiver is told the request is no longer available.
- A user searches for their own username: they are not offered as a result (existing behaviour from feature 002).
- A user searches for someone who is already in a couple: that user is shown but cannot be sent a request; the searcher is told the user is not available.
- A user who has been rejected sends another request to the same person: this is allowed. Repeated unwanted requests are limited by the request rate limit (FR-018).
- A user whose account is deleted while they have pending requests or an active couple: their pending requests are closed and their couple ends.

## Requirements *(mandatory)*

### Functional Requirements

**Search and availability**

- **FR-001**: Only signed-in users who are not in a couple MUST be able to use partner search. Users in a couple MUST be shown their current couple instead.
- **FR-002**: Partner search MUST keep the existing behaviour from feature 002: exact, case-insensitive username match; only username and display name shown; the searcher's own account never returned; rate-limited.
- **FR-003**: When a search result is a user who is already in a couple, the system MUST show that the user is unavailable and MUST NOT allow a request to be sent. It MUST NOT reveal who that user is in a couple with.

**Requests**

- **FR-004**: A user not in a couple MUST be able to send a couple request to a user found by search who is also not in a couple.
- **FR-005**: A user MUST have at most one outgoing pending request at a time. A user MAY have several incoming pending requests.
- **FR-006**: The system MUST NOT allow a request to oneself, a duplicate pending request between the same two users, or a request to or from a user who is already in a couple.
- **FR-007**: If a user tries to send a request to someone who already has a pending request to them, the system MUST NOT create a second request and MUST direct the user to the existing incoming request.
- **FR-008**: The sender MUST be able to see the status of their outgoing request (pending or declined) and cancel it while it is pending.
- **FR-009**: The receiver MUST be able to see their incoming pending requests, each showing the sender's display name and username only.
- **FR-010**: Only the receiver of a request MUST be able to accept or reject it. Only the sender MUST be able to cancel it.

**Creating a couple**

- **FR-011**: Accepting a request MUST create a couple of exactly those two users, provided neither is in a couple at that moment. Otherwise the accept MUST fail with a clear message and no couple is created.
- **FR-012**: When a couple is created, every other pending request sent by or to either member MUST be closed automatically.
- **FR-013**: The system MUST guarantee that a user is never in more than one active couple, even when actions happen at the same time.
- **FR-014**: Rejecting a request MUST close it without creating a couple. The sender MUST then be free to send a new request.

**Ending a couple**

- **FR-015**: Either member of a couple MUST be able to end it after an explicit confirmation step. The other member's consent is not required. The confirmation MUST clearly warn that ending the couple permanently deletes the couple's reviews.
- **FR-016**: Ending a couple MUST make both members single immediately, so both can search for and request partners again.
- **FR-017**: When a couple ends, all data belonging to that couple (including both partners' reviews and reviewed items) MUST be permanently deleted. This cannot be undone. If the same two users reconnect later, they start a new, empty couple.

**Validation, authorization, and limits**

- **FR-018**: The system MUST limit how many couple requests one user can send (no more than 10 per hour) to prevent spam.
- **FR-019**: Every request and couple action (send, cancel, accept, reject, end) MUST be authorized against the current user and MUST be refused for anyone who is not the right party.
- **FR-020**: All refused actions MUST give the user a clear, plain-language reason (for example "Harry is already in a couple", "You already have a pending request").

### Key Entities

- **Couple Request**: An invitation from one user (sender) to another (receiver) to form a couple. It has a status: pending, accepted, declined, or cancelled. It also records when it was sent and when it was answered.
- **Couple**: Exactly two users who have both agreed to connect. It has a start date. A user belongs to at most one couple. Ending a couple deletes it along with all data it owns.
- **User**: Existing account (with username from feature 002). A user is either single or a member of one active couple.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Two single users who know each other's usernames can go from sign-in to being a couple in under 2 minutes in total (send plus accept).
- **SC-002**: 0 couples ever have more than two members, and 0 users are ever in more than one active couple, including under simultaneous actions.
- **SC-003**: 0 couples are created without the receiver explicitly accepting.
- **SC-004**: 100% of attempts to request, accept, or end on behalf of someone else are refused.
- **SC-005**: A member can end their couple in under 30 seconds, and both members can search for a new partner straight away.
- **SC-007**: After a couple ends, 0 of its reviews or other couple data remain accessible or stored.
- **SC-006**: 100% of refused actions show the user a reason they can understand without help.

## Assumptions

- This builds on the existing exact-username partner search (feature 002). It does not add partial or name-based search.
- "Searching should not bring back your own couple" means: you never see yourself in results, and users in a couple cannot search at all, so they never see their own partner as a requestable result.
- Requests do not expire on their own. They stay pending until accepted, rejected, or cancelled, or until either user joins a couple.
- Users are notified of requests in the app only (on their next visit). Email or push notifications are out of scope.
- Ending a couple is one-sided and immediate after confirmation. There is no "undo"; the two users can reconnect by sending a new request, which starts an empty couple.
- The receiver is not told the sender's email address, and the sender is not told why a request was declined.
- Blocking a user is out of scope. The request rate limit is the only protection against repeated requests for now.
- Reviews do not exist yet. FR-017 sets the outcome for future couple data: review features must make sure all of a couple's data is deleted when the couple ends (Constitution Principle III).
- The partner who did not end the couple gets no warning before deletion. They find out on their next visit.
