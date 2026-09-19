<?php
/**
 * Session card — one reusable component for every session/request status.
 * Used by: Sessions, Learner Requests (pending/accepted/rejected), the
 * dashboards' "Upcoming Sessions" lists, Availability's compact preview.
 *
 * Usage:
 *   require_once __DIR__ . '/../components/session-card.php';
 *   foreach ($sessions as $session) { ukn_session_card($session); }
 *
 * $session shape:
 *   [
 *     'id'       => 101,                     // optional — powers data-session-id
 *                                             // and the other data-session-*
 *                                             // reads assets/js/pages/sessions.js
 *                                             // uses to accept/reject/cancel
 *                                             // THIS exact card in place
 *     'counterparty'     => 'Rahim Ahmed',   // the OTHER person (mentor, if viewer is a learner; learner, if viewer is a mentor)
 *     'counterpartyInitials' => 'RA',
 *     'counterpartyHref' => null,             // optional — links the counterparty's
 *                                             // name to their public profile
 *     'skill'    => 'Python',
 *     'day'      => '17', 'month' => 'Sep',
 *     'time'     => 'Wed 6:00pm', 'duration' => '60 min',
 *     'status'   => 'pending' | 'accepted' | 'upcoming' | 'completed' | 'rejected' | 'cancelled',
 *     'message'  => null,                    // request message (pending) OR
 *                                             // cancellation reason (cancelled) —
 *                                             // one generic note field, same as
 *                                             // the approved design
 *     'detailsHref' => 'index.php?page=session-details',
 *     'ratingStatus' => null,                // completed only: null (no rating
 *                                             // UI — e.g. this is the viewer's
 *                                             // own mentor-side session) |
 *                                             // 'unrated' (show Rate Mentor) |
 *                                             // 'rated' (show the given ratingValue)
 *     'ratingValue'  => null,                // e.g. 5.0, shown when ratingStatus is 'rated'
 *   ]
 *
 * Accept/Reject/Cancel/Rate are frontend-only mock actions — no backend,
 * no real session state change. Accept fires immediately
 * (assets/js/pages/sessions.js listens for [data-session-accept]); Reject
 * and Cancel reuse the one shared modals/delete-confirmation-modal.php
 * (assets/js/core/modal.js already populates its title/message from the
 * data-delete-* attributes below — unchanged — while sessions.js reads the
 * data-reject-session / data-cancel-session marker to know which state
 * transition to actually apply once confirmed); Rate Mentor reuses the one
 * shared modals/rating-modal.php via the same data-rating-* +
 * assets/js/core/modal.js contextual-modal pattern already used for
 * Request Session (see components/mentor-card.php).
 */
if (!function_exists('ukn_session_card')) {
    function ukn_session_card(array $session): void
    {
        $session += [
            'id' => null, 'counterparty' => 'Member', 'counterpartyInitials' => '?', 'counterpartyHref' => null,
            'skill' => '', 'day' => '', 'month' => '', 'time' => '', 'duration' => '',
            'status' => 'upcoming', 'message' => null, 'detailsHref' => '#',
            'ratingStatus' => null, 'ratingValue' => null,
        ];

        $statusMeta = [
            'pending'   => ['label' => 'Pending',   'class' => 'ukn-status-accent'],
            'accepted'  => ['label' => 'Accepted',  'class' => 'ukn-status-success'],
            'upcoming'  => ['label' => 'Upcoming',  'class' => 'ukn-status-accent'],
            'completed' => ['label' => 'Completed', 'class' => 'ukn-status-success'],
            'rejected'  => ['label' => 'Rejected',  'class' => 'ukn-status-danger'],
            'cancelled' => ['label' => 'Cancelled', 'class' => 'ukn-status-danger'],
        ];
        $meta = $statusMeta[$session['status']] ?? $statusMeta['upcoming'];
        ?>
        <div
          class="card mb-3"
          data-session-id="<?= htmlspecialchars((string) $session['id']) ?>"
          data-session-status="<?= htmlspecialchars($session['status']) ?>"
          data-session-details-href="<?= htmlspecialchars($session['detailsHref']) ?>"
        >
          <div class="card-body ukn-session-card">
            <div class="ukn-session-card__date">
              <span class="ukn-day"><?= htmlspecialchars($session['day']) ?></span>
              <span class="ukn-eyebrow"><?= htmlspecialchars($session['month']) ?></span>
            </div>
            <div class="ukn-session-card__body">
              <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                <h3 class="ukn-h4 mb-0">
                  <?= htmlspecialchars($session['skill']) ?> with
                  <?php if ($session['counterpartyHref']): ?>
                    <a href="<?= htmlspecialchars($session['counterpartyHref']) ?>"><?= htmlspecialchars($session['counterparty']) ?></a>
                  <?php else: ?>
                    <?= htmlspecialchars($session['counterparty']) ?>
                  <?php endif; ?>
                </h3>
                <span class="ukn-status <?= $meta['class'] ?>" data-session-status-badge><?= $meta['label'] ?></span>
              </div>
              <div class="ukn-body-sm">
                <?= htmlspecialchars($session['time']) ?><?php if ($session['duration']): ?> · <?= htmlspecialchars($session['duration']) ?><?php endif; ?>
              </div>
              <?php if ($session['message']): ?>
                <p class="ukn-body-sm ukn-session-card__message mb-0"><?= htmlspecialchars($session['message']) ?></p>
              <?php endif; ?>
            </div>
            <div class="ukn-session-card__actions" data-session-actions>
              <?php switch ($session['status']):
                case 'pending': ?>
                  <button type="button" class="btn btn-primary btn-sm" data-session-accept>Accept</button>
                  <button
                    type="button"
                    class="btn btn-outline-danger btn-sm"
                    data-bs-toggle="modal"
                    data-bs-target="#deleteConfirmationModal"
                    data-delete-title="Reject this request?"
                    data-delete-message="The learner will be notified that this request was not accepted."
                    data-delete-confirm-label="Reject"
                    data-success-message="Session request rejected."
                    data-reject-session
                  >Reject</button>
                  <?php break;
                case 'accepted': ?>
                  <a href="<?= htmlspecialchars($session['detailsHref']) ?>" class="btn btn-outline-secondary btn-sm">View Details</a>
                  <?php break;
                case 'upcoming': ?>
                  <a href="<?= htmlspecialchars($session['detailsHref']) ?>" class="btn btn-outline-secondary btn-sm">View Details</a>
                  <button
                    type="button"
                    class="btn btn-outline-danger btn-sm"
                    data-bs-toggle="modal"
                    data-bs-target="#deleteConfirmationModal"
                    data-delete-title="Cancel Session?"
                    data-delete-message="Are you sure you want to cancel this session?"
                    data-delete-confirm-label="Cancel Session"
                    data-success-message="Session cancelled."
                    data-cancel-session
                  >Cancel Session</button>
                  <?php break;
                case 'completed': ?>
                  <a href="<?= htmlspecialchars($session['detailsHref']) ?>" class="btn btn-outline-secondary btn-sm">View Details</a>
                  <?php if ($session['ratingStatus'] === 'unrated'): ?>
                    <button
                      type="button"
                      class="btn btn-primary btn-sm"
                      data-bs-toggle="modal"
                      data-bs-target="#ratingModal"
                      data-rating-mentor="<?= htmlspecialchars($session['counterparty']) ?>"
                      data-rating-skill="<?= htmlspecialchars($session['skill']) ?>"
                      data-rating-date="<?= htmlspecialchars(trim($session['day'] . ' ' . $session['month'])) ?>"
                    >Rate Mentor</button>
                  <?php elseif ($session['ratingStatus'] === 'rated'): ?>
                    <span class="ukn-status ukn-status-success">Rated <?= htmlspecialchars((string) $session['ratingValue']) ?></span>
                  <?php endif; ?>
                  <?php break;
                default: ?>
                  <a href="<?= htmlspecialchars($session['detailsHref']) ?>" class="btn btn-outline-secondary btn-sm">View Details</a>
              <?php endswitch; ?>
            </div>
          </div>
        </div>
        <?php
    }
}
