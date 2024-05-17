<?php

/*
 *
 * File ini bagian dari:
 *
 * OpenSID
 *
 * Sistem informasi desa sumber terbuka untuk memajukan desa
 *
 * Aplikasi dan source code ini dirilis berdasarkan lisensi GPL V3
 *
 * Hak Cipta 2009 - 2015 Combine Resource Institution (http://lumbungkomunitas.net/)
 * Hak Cipta 2016 - 2024 Perkumpulan Desa Digital Terbuka (https://opendesa.id)
 *
 * Dengan ini diberikan izin, secara gratis, kepada siapa pun yang mendapatkan salinan
 * dari perangkat lunak ini dan file dokumentasi terkait ("Aplikasi Ini"), untuk diperlakukan
 * tanpa batasan, termasuk hak untuk menggunakan, menyalin, mengubah dan/atau mendistribusikan,
 * asal tunduk pada syarat berikut:
 *
 * Pemberitahuan hak cipta di atas dan pemberitahuan izin ini harus disertakan dalam
 * setiap salinan atau bagian penting Aplikasi Ini. Barang siapa yang menghapus atau menghilangkan
 * pemberitahuan ini melanggar ketentuan lisensi Aplikasi Ini.
 *
 * PERANGKAT LUNAK INI DISEDIAKAN "SEBAGAIMANA ADANYA", TANPA JAMINAN APA PUN, BAIK TERSURAT MAUPUN
 * TERSIRAT. PENULIS ATAU PEMEGANG HAK CIPTA SAMA SEKALI TIDAK BERTANGGUNG JAWAB ATAS KLAIM, KERUSAKAN ATAU
 * KEWAJIBAN APAPUN ATAS PENGGUNAAN ATAU LAINNYA TERKAIT APLIKASI INI.
 *
 * @package   OpenSID
 * @author    Tim Pengembang OpenDesa
 * @copyright Hak Cipta 2009 - 2015 Combine Resource Institution (http://lumbungkomunitas.net/)
 * @copyright Hak Cipta 2016 - 2024 Perkumpulan Desa Digital Terbuka (https://opendesa.id)
 * @license   http://www.gnu.org/licenses/gpl.html GPL V3
 * @link      https://github.com/OpenSID/OpenSID
 *
 */

defined('BASEPATH') || exit('No direct script access allowed');

class Kelompok_master_model extends MY_Model
{
    protected $table = 'kelompok_master';
    protected $tipe  = 'kelompok';

    public function set_tipe(string $tipe)
    {
        $this->tipe = $tipe;

        return $this;
    }

    public function autocomplete()
    {
        return $this->autocomplete_str('kelompok', $this->table);
    }

    private function search_sql()
    {
        if ($search = $this->session->cari) {
            $this->db
                ->group_start()
                ->like('u.kelompok', $search)
                ->or_like('u.deskripsi', $search)
                ->group_end();
        }

        return $this->db;
    }

    public function paging($p = 1)
    {
        $jml_data = $this->list_data_sql()->count_all_results();

        return $this->paginasi($p, $jml_data);
    }

    private function list_data_sql()
    {
        $this->config_id('u')
            ->select('u.*')
            ->select('(SELECT COUNT(k.id) FROM kelompok k WHERE k.id_master = u.id) AS jumlah')
            ->from("{$this->table} u")
            ->where('tipe', $this->tipe);

        $this->search_sql();

        return $this->db;
    }

    // $limit = 0 mengambil semua
    public function list_data($o = 0, $offset = 0, $limit = 0)
    {
        switch ($o) {
            case 1: $this->db->order_by('u.kelompok');
                break;

            case 2: $this->db->order_by('u.kelompok', 'desc');
                break;

            default: $this->db->order_by('u.kelompok');
                break;
        }

        $this->list_data_sql();

        return $this->db
            ->limit($limit, $offset)
            ->get()
            ->result_array();
    }

    public function insert()
    {
        $data              = $this->validasi($this->input->post());
        $data['config_id'] = $this->config_id;
        $outp              = $this->db->insert($this->table, $data);

        status_sukses($outp); //Tampilkan Pesan
    }

    public function update($id = 0)
    {
        $data = $this->validasi($this->input->post());
        $outp = $this->config_id()->where('id', $id)->update($this->table, $data);

        status_sukses($outp); //Tampilkan Pesan
    }

    private function validasi($post)
    {
        if ($post['id']) {
            $data['id'] = bilangan($post['id']);
        }
        $data['kelompok']  = nama_terbatas($post['kelompok']);
        $data['deskripsi'] = htmlentities($post['deskripsi']);
        $data['tipe']      = $this->tipe;

        return $data;
    }

    public function delete($id = '', $semua = false)
    {
        $this->get_kelompok_master($id) ?? show_404();

        if ($this->deleteJumlahNotNull($id)) {
            session_error('tidak dapat dihapus karena masih terdapat data kelompok');

            return false;
        }

        $outp = $this->config_id()
            ->where('id', $id)
            ->where('tipe', $this->tipe)
            ->delete($this->table);

        status_sukses($outp, $semua); //Tampilkan Pesan
    }

    public function delete_all()
    {
        $this->session->success = 1;

        $id_cb = $_POST['id_cb'];

        foreach ($id_cb as $id) {
            $this->delete($id, true);
        }
    }

    public function get_kelompok_master($id = 0)
    {
        return $this->config_id()
            ->where([
                'id'   => $id,
                'tipe' => $this->tipe,
            ])
            ->get($this->table)
            ->row();
    }

    public function deleteJumlahNotNull($id = 0)
    {
        return $this->config_id()
            ->where('id_master', $id)
            ->get('kelompok')
            ->row();
    }
}
