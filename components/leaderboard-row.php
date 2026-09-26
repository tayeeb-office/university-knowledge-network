<?php
require_once __DIR__ . '/../backend/helpers/avatars.php';
if (!function_exists('ukn_leaderboard_row')) {
    function ukn_leaderboard_row(array $row, array $options = []): void
    {
        $row += [
            'href' => null, 'category' => '', 'points' => null, 'pointType' => 'Points',
            'rating' => null, 'sessions' => null, 'activityLabel' => 'sessions',
            'rankChange' => null, 'isCurrentUser' => false,
        ];
        $rank = (int) $row['rank'];
        $topClass = $rank >= 1 && $rank <= 3 ? ' ukn-leaderboard-row--top' . $rank : '';
        $youClass = $row['isCurrentUser'] ? ' ukn-leaderboard-row--you' : '';
        if (($options['variant'] ?? 'row') === 'podium') {
            $ordinal = ['', '1st', '2nd', '3rd'][$rank] ?? "#{$rank}";
            ?>
            <div class="ukn-leaderboard-podium-card<?= $topClass ?><?= $youClass ?>">
              <div class="ukn-eyebrow ukn-leaderboard-podium-card__place">#<?= $rank ?> &middot; <?= $ordinal ?></div>
              <?= uknAvatarHtml($row['avatar_path'] ?? null, (string) $row['initials'], 'ukn-avatar ukn-avatar-lg') ?>
              <div class="fw-bold mt-2">
                <?php if ($row['href']): ?>
                  <a href="<?= htmlspecialchars($row['href']) ?>"><?= htmlspecialchars($row['name']) ?></a>
                <?php else: ?>
                  <?= htmlspecialchars($row['name']) ?>
                <?php endif; ?>
                <?php if ($row['isCurrentUser']): ?>
                  <span class="ukn-status ukn-status-accent">You</span>
                <?php endif; ?>
              </div>
              <?php if ($row['category']): ?><div class="ukn-body-sm"><?= htmlspecialchars($row['category']) ?></div><?php endif; ?>
              <?php if ($row['points'] !== null): ?>
                <div class="ukn-leaderboard-podium-card__points mt-2"><?= (int) $row['points'] ?></div>
                <div class="ukn-eyebrow"><?= htmlspecialchars($row['pointType']) ?></div>
              <?php endif; ?>
              <?php if ($row['rating'] !== null): ?>
                <div class="ukn-body-sm mt-1">★ <?= htmlspecialchars((string) $row['rating']) ?></div>
              <?php endif; ?>
            </div>
            <?php
            return;
        }
        ?>
        <div class="ukn-leaderboard-row<?= $topClass ?><?= $youClass ?>" data-leaderboard-row data-rank-name="<?= htmlspecialchars(strtolower($row['name'])) ?>" data-rank-category="<?= htmlspecialchars(strtolower($row['category'])) ?>">
          <span class="ukn-leaderboard-row__rank">#<?= $rank ?></span>
          <?= uknAvatarHtml($row['avatar_path'] ?? null, (string) $row['initials'], 'ukn-avatar flex-shrink-0') ?>
          <div class="flex-fill ukn-min-w-0">
            <div class="fw-bold ukn-truncate">
              <?php if ($row['href']): ?>
                <a href="<?= htmlspecialchars($row['href']) ?>"><?= htmlspecialchars($row['name']) ?></a>
              <?php else: ?>
                <?= htmlspecialchars($row['name']) ?>
              <?php endif; ?>
              <?php if ($row['isCurrentUser']): ?>
                <span class="ukn-status ukn-status-accent">You</span>
              <?php endif; ?>
            </div>
            <?php if ($row['category']): ?><div class="ukn-body-sm ukn-truncate"><?= htmlspecialchars($row['category']) ?></div><?php endif; ?>
          </div>
          <?php if ($row['rating'] !== null): ?>
            <span class="ukn-body-sm flex-shrink-0 d-none d-sm-inline">★ <?= htmlspecialchars((string) $row['rating']) ?></span>
          <?php endif; ?>
          <?php if ($row['sessions'] !== null): ?>
            <span class="ukn-body-sm flex-shrink-0 d-none d-sm-inline"><?= (int) $row['sessions'] ?> <?= htmlspecialchars($row['activityLabel']) ?></span>
          <?php endif; ?>
          <?php if (is_array($row['rankChange'])):
            $direction = $row['rankChange']['direction'] ?? 'none';
            $amount = (int) ($row['rankChange']['amount'] ?? 0);
            $symbol = $direction === 'up' ? '▲' : ($direction === 'down' ? '▼' : '—');
            $colorClass = $direction === 'up' ? 'ukn-text-success' : ($direction === 'down' ? 'ukn-text-danger' : 'ukn-text-muted');
            $srText = $direction === 'up' ? "Up {$amount} place" . ($amount === 1 ? '' : 's')
                : ($direction === 'down' ? "Down {$amount} place" . ($amount === 1 ? '' : 's') : 'No rank change');
          ?>
            <span class="ukn-body-sm flex-shrink-0 d-none d-md-inline-flex align-items-center gap-1 <?= $colorClass ?>">
              <span aria-hidden="true"><?= $symbol ?><?= $direction !== 'none' ? $amount : '' ?></span>
              <span class="ukn-visually-hidden"><?= $srText ?></span>
            </span>
          <?php endif; ?>
          <?php if ($row['points'] !== null): ?>
            <span class="fw-bold flex-shrink-0 text-end"><?= (int) $row['points'] ?> <span class="ukn-body-sm fw-normal d-block d-sm-inline"><?= htmlspecialchars($row['pointType']) ?></span></span>
          <?php endif; ?>
        </div>
        <?php
    }
}