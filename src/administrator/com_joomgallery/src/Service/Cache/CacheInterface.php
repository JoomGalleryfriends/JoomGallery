<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Cache;

\defined('_JEXEC') || die;

/**
 * Namespace-specific cache operations.
 * @since 4.5.0
 */
interface CacheInterface
{
  public function __construct(string $namespace);
  public function configure(?string $scope = null, bool $session = false, int $maxAge = 0): void;
  public function initialise(): void;
  public function getRevision(): ?string;
  public function has(string $key): bool;
  public function get(string $key, mixed $default = null): mixed;
  public function set(string $key, mixed $value = null, int $limit = 0): mixed;
  public function remove(string|false $pattern = false, bool $decodeBase64 = false): void;
  public function prune(callable $keep, int $limit = 0): void;
  public function persist(): void;
  public function persistAll(): void;
}
