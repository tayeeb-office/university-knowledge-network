<?php
if (!function_exists('ukn_session_action_form')) {
    /**
     * A POST form for a session action (backend/sessions/<action>.php). With $formId it is a
     * hidden form submitted by the delete-confirmation modal (data-delete-form); otherwise it
     * wraps a visible submit button labelled $label.
     */
    function ukn_session_action_form(int $sessionId, string $action, ?string $formId = null, string $label = '', string $buttonClass = 'btn btn-primary btn-sm'): void
    {
        ?>
        <form action="backend/sessions/<?= htmlspecialchars($action) ?>.php" method="post" class="d-inline-flex m-0"<?= $formId ? ' id="' . htmlspecialchars($formId) . '" hidden' : '' ?>>
          <?= function_exists('csrfField') ? csrfField() : '' ?>
          <?= function_exists('uknReturnToField') ? uknReturnToField() : '' ?>
          <input type="hidden" name="session_id" value="<?= $sessionId ?>">
          <?php if (!$formId): ?><button type="submit" class="<?= htmlspecialchars($buttonClass) ?>"><?= htmlspecialchars($label) ?></button><?php endif; ?>
        </form>
        <?php
    }
}
if (!function_exists('ukn_session_card')) {
    function ukn_session_card(array $session): void
    {
        $session += [
            'id' => null, 'counterparty' => 'Member', 'counterpartyInitials' => '?', 'counterpartyHref' => null,
            'skill' => '', 'day' => '', 'month' => '', 'time' => '', 'duration' => '',
            'status' => 'upcoming', 'message' => null, 'detailsHref' => '#',
            'ratingStatus' => null, 'ratingValue' => null, 'viewer' => null,
        ];
        // Which side of the session the viewer is on decides the actions offered; pending cards
        // default to the mentor side (learner requests). Without an id only links are shown.
        $sessionId = (int) $session['id'];
        $viewer = $session['viewer'] ?? ($session['status'] === 'pending' ? 'mentor' : 'learner');
        $canAct = $sessionId > 0;
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
                  <?php if ($canAct && $viewer === 'mentor'): ?>
                    <?php ukn_session_action_form($sessionId, 'accept', null, 'Accept'); ?>
                    <button
                      type="button"
                      class="btn btn-outline-danger btn-sm"
                      data-bs-toggle="modal"
                      data-bs-target="#deleteConfirmationModal"
                      data-delete-title="Reject this request?"
                      data-delete-message="The learner will see that this request was not accepted."
                      data-delete-confirm-label="Reject"
                      data-delete-form="sessionReject-<?= $sessionId ?>"
                    >Reject</button>
                    <?php ukn_session_action_form($sessionId, 'reject', 'sessionReject-' . $sessionId); ?>
                  <?php elseif ($canAct): ?>
                    <a href="<?= htmlspecialchars($session['detailsHref']) ?>" class="btn btn-outline-secondary btn-sm">View Details</a>
                    <button
                      type="button"
                      class="btn btn-outline-danger btn-sm"
                      data-bs-toggle="modal"
                      data-bs-target="#deleteConfirmationModal"
                      data-delete-title="Cancel this request?"
                      data-delete-message="The mentor will no longer see this request."
                      data-delete-confirm-label="Cancel Request"
                      data-delete-form="sessionCancel-<?= $sessionId ?>"
                    >Cancel Request</button>
                    <?php ukn_session_action_form($sessionId, 'cancel', 'sessionCancel-' . $sessionId); ?>
                  <?php endif; ?>
                  <?php break;
                case 'accepted': ?>
                  <a href="<?= htmlspecialchars($session['detailsHref']) ?>" class="btn btn-outline-secondary btn-sm">View Details</a>
                  <?php break;
                case 'upcoming': ?>
                  <a href="<?= htmlspecialchars($session['detailsHref']) ?>" class="btn btn-outline-secondary btn-sm">View Details</a>
                  <?php if ($canAct): ?>
                    <button
                      type="button"
                      class="btn btn-outline-danger btn-sm"
                      data-bs-toggle="modal"
                      data-bs-target="#deleteConfirmationModal"
                      data-delete-title="Cancel Session?"
                      data-delete-message="Are you sure you want to cancel this session? Cancelling within 6 hours of the start costs 10 points."
                      data-delete-confirm-label="Cancel Session"
                      data-delete-form="sessionCancel-<?= $sessionId ?>"
                    >Cancel Session</button>
                    <?php ukn_session_action_form($sessionId, 'cancel', 'sessionCancel-' . $sessionId); ?>
                  <?php endif; ?>
                  <?php break;
                case 'completed': ?>
                  <a href="<?= htmlspecialchars($session['detailsHref']) ?>" class="btn btn-outline-secondary btn-sm">View Details</a>
                  <?php if ($canAct && $viewer === 'learner' && $session['ratingStatus'] === 'unrated'): ?>
                    <button
                      type="button"
                      class="btn btn-primary btn-sm"
                      data-bs-toggle="modal"
                      data-bs-target="#ratingModal"
                      data-rating-session-id="<?= $sessionId ?>"
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