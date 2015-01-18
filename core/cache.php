<?php


/**
*
* @package phpBB Gallery
* @copyright (c) 2014 nickvergessen
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbgallery\core;

class cache
{
	private $phpbb_cache;
	private $phpbb_db;

	public function __construct(\phpbb\cache\service $cache, \phpbb\db\driver\driver_interface $db)
	{
		$this->phpbb_cache = $cache;
		$this->phpbb_db = $db;
	}

	public function get($data = 'albums')
	{
		switch ($data)
		{
			case 'albums':
				return $this->get_albums();
			default:
				return false;
		}
	}

	public function get_albums()
	{
		static $albums;

		global $table_prefix;

		if (isset($albums))
		{
			return $albums;
		}

		if (($albums = $this->phpbb_cache->get('_albums')) === false)
		{
			$sql = 'SELECT a.album_id, a.parent_id, a.album_name, a.album_type, a.left_id, a.right_id, a.album_user_id, a.display_in_rrc, a.album_auth_access
				FROM ' . $table_prefix. 'gallery_albums a
				LEFT JOIN ' . USERS_TABLE . ' u
					ON (u.user_id = a.album_user_id)
				ORDER BY u.username_clean, a.album_user_id, a.left_id ASC';
			$result = $this->phpbb_db->sql_query($sql);

			$albums = array();
			while ($row = $this->phpbb_db->sql_fetchrow($result))
			{
				$albums[(int) $row['album_id']] = array(
					'album_id'			=> (int) $row['album_id'],
					'parent_id'			=> (int) $row['parent_id'],
					'album_name'		=> $row['album_name'],
					'album_type'		=> (int) $row['album_type'],
					'left_id'			=> (int) $row['left_id'],
					'right_id'			=> (int) $row['right_id'],
					'album_user_id'		=> (int) $row['album_user_id'],
					'display_in_rrc'	=> (bool) $row['display_in_rrc'],
					'album_auth_access'	=> (int) $row['album_auth_access'],
				);
			}
			$this->phpbb_db->sql_freeresult($result);
			$this->phpbb_cache->put('_albums', $albums);
		}

		return $albums;
	}

	/**
	* Get images cache - get some images and put them in cache
	* @param	(array)	$image_ids_array	Array of images to be put in cache
	* return 	(array)	$images				Array of the information we have for that images
	*/
	public function get_images($image_ids_array)
	{
		static $images;

		global $table_prefix;

		if (isset($images))
		{
			return $images;
		}

		if (($albums = $this->phpbb_cache->get('_images')) === false)
		{
			$sql_array = array(
				'SELECT'	=> 'i.*, a.album_name',
				'FROM'	=> array(
					$table_prefix . 'gallery_images'	=> 'i',
					$table_prefix . 'gallery_albums'	=> 'a'
				),
				'WHERE'	=> $this->phpbb_db->sql_in_set('image_id', $image_ids_array)
			);
			$sql = $this->phpbb_db->sql_build_query('SELECT', $sql_array);
			$result = $this->phpbb_db->sql_query($sql);

			$images = array();
			while ($row = $this->phpbb_db->sql_fetchrow($result))
			{
				$images[(int) $row['image_id']] = array(
					'image_id'				=> $row['image_id'],
					'image_filename'		=> $row['image_filename'],
					'image_name'			=> $row['image_name'],
					'image_name_clean'		=> $row['image_name_clean'],
					'image_desc'			=> $row['image_desc'],
					'image_desc_uid'		=> $row['image_desc_uid'],
					'image_desc_bitfield'	=> $row['image_desc_bitfield'],
					'image_user_id'			=> $row['image_user_id'],
					'image_username'		=> $row['image_username'],
					'image_username_clean'	=> $row['image_username_clean'],
					'image_user_colour'		=> $row['image_user_colour'],
					'image_user_ip'			=> $row['image_user_ip'],
					'image_time'			=> $row['image_time'],
					'image_album_id'		=> $row['image_album_id'],
					'image_view_count'		=> $row['image_view_count'],
					'image_status'			=> $row['image_status'],
					'image_filemissing'		=> $row['image_filemissing'],
					'image_rates'			=> $row['image_rates'],
					'image_rate_points'		=> $row['image_rate_points'],
					'image_rate_avg'		=> $row['image_rate_avg'],
					'image_comments'		=> $row['image_comments'],
					'image_last_comment'	=> $row['image_last_comment'],
					'image_allow_comments'	=> $row['image_allow_comments'],
					'image_favorited'		=> $row['image_favorited'],
					'image_reported'		=> $row['image_reported'],
					'filesize_upload'		=> $row['filesize_upload'],
					'filesize_medium'		=> $row['filesize_medium'],
					'filesize_cache'		=> $row['filesize_cache'],
					'album_name'			=> $row['album_name'],
				);
			}
			$this->phpbb_db->sql_freeresult($result);
			$this->phpbb_cache->put('_images', $images);
		}
		return $images;
	}

	/**
	* Destroy images cache - if we had updated image information or we want other set - we will have to destroy cache
	*/
	public function destroy_images()
	{
		$this->phpbb_cache->destroy('_images');
	}
}
