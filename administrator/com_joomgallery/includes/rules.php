<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Array for mapping the callable rule names to actual acl rules
 * and to define available assets (content types) and the own tag.
 *
 * @package JoomGallery
 * @since   4.0.0
 */
$rules_map_array = [
  'add'       => [  'name'   => 'add',
                    'rule'        => 'core.create',
                    'assets'      => [ '.',
                                      '.image',
                                      '.imagetype',
                                      '.category',
                                      '.config',
                                      '.tag',
                                      '.task',
                                      '.collection',
                                    ],
                    'own'        => 'inown',
                    'own-assets' => [ '.',
                                      '.image',
                                      '.category'
                                    ],
                  ],
  'admin'     => [  'name'       => 'admin',
                    'rule'       => 'core.admin',
                    'assets'     => [ '.', ],
                    'own'        => false,
                    'own-assets' => [],
                  ],
  'connect'   => [  'name'       => 'connect',
                    'rule'       => 'joom.connect',
                    'assets'     => [ '.',
                                      '.collection'
                                    ],
                    'own'        => 'inown',
                    'own-assets' => [ '.',
                                      '.collection',
                                    ],
                  ],
  'delete'    => [  'name'       => 'delete',
                    'rule'       => 'core.delete',
                    'assets'     => [ '.',
                                      '.image',
                                      '.imagetype',
                                      '.category',
                                      '.config',
                                      '.tag',
                                      '.task',
                                      '.collection',
                                ],
                    'own'        => 'own',
                    'own-assets' => [ '.',
                                      '.image',
                                      '.category',
                                    ],
                  ],
  'edit'      => [  'name'       => 'edit',
                    'rule'       => 'core.edit',
                    'assets'     => [ '.',
                                      '.image',
                                      '.imagetype',
                                      '.category',
                                      '.config',
                                      '.tag',
                                      '.task',
                                      '.collection',
                                    ],
                    'own'        => 'own',
                    'own-assets' => [ '.',
                                      '.image',
                                      '.category',
                                      '.config',
                                      '.tag',
                                    ],
                  ],
  'editstate' => [  'name'       => 'editstate',
                    'rule'       => 'core.edit.state',
                    'assets'     => [ '.',
                                      '.image',
                                      '.category',
                                      '.config',
                                      '.tag',
                                      '.task',
                                      '.collection',
                                    ],
                    'own'        => false,
                    'own-assets' => [],
                  ],
  'manage'    => [  'name'       => 'manage',
                    'rule'       => 'core.manage',
                    'assets'     => [ '.' ],
                    'own'        => false,
                    'own-assets' => [],
                  ],
];
