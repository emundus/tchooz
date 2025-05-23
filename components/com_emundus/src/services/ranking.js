import client from './axiosClient';

export default {
	async getMyRanking(pagination, ordering, packageId = 0) {
		try {
			let urlparams =
				'&page=' +
				pagination.page +
				'&limit=' +
				pagination.perPage +
				'&order_by=' +
				ordering.orderBy +
				'&order=' +
				ordering.order +
				'&order_by_hierarchy=' +
				ordering.orderHierarchy;
			if (packageId > 0) {
				urlparams += '&package_id=' + packageId;
			}

			const response = await client().get(
				'index.php?option=com_emundus&controller=ranking&task=getMyFilesToRank' + urlparams,
			);
			return response.data;
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getOtherHierarchyRankings() {
		try {
			const response = await client().get(
				'index.php?option=com_emundus&controller=ranking&task=getOtherRankingsICanSee',
			);
			return response.data;
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async updateRanking(id, rank, hierarchy_id) {
		try {
			const Form = new FormData();
			Form.append('id', id);
			Form.append('rank', rank);
			Form.append('hierarchy_id', hierarchy_id);

			const response = await client().post(
				'index.php?option=com_emundus&controller=ranking&task=updateFileRanking',
				Form,
				{
					headers: {
						'Content-Type': 'multipart/form-data',
					},
				},
			);
			return response.data;
		} catch (e) {
			return {
				status: false,
				msg: e.response.data,
			};
		}
	},
	async lockRanking(id, lock) {
		try {
			const Form = new FormData();
			Form.append('id', id);
			Form.append('lock', lock);

			const response = await client().post(
				'index.php?option=com_emundus&controller=ranking&task=lockFilesOfHierarchyRanking',
				Form,
				{
					headers: {
						'Content-Type': 'multipart/form-data',
					},
				},
			);
			return response.data;
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async askToLockRankings(users, hierarchies) {
		try {
			const Form = new FormData();
			Form.append('users', JSON.stringify(users));
			Form.append('hierarchies', JSON.stringify(hierarchies));

			const response = await client().post(
				'index.php?option=com_emundus&controller=ranking&task=askToLockRankings',
				Form,
				{
					headers: {
						'Content-Type': 'multipart/form-data',
					},
				},
			);
			return response.data;
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getPackages() {
		try {
			const response = await client().get('index.php?option=com_emundus&controller=ranking&task=getPackages');
			return response.data;
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getHierarchiesUserCanSee() {
		try {
			const response = await client().get(
				'index.php?option=com_emundus&controller=ranking&task=getHierarchiesUserCanSee',
			);
			return response.data;
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	exportRanking(packageIds, hierarchyIds, columns) {
		try {
			const Form = new FormData();
			Form.append('packageIds', JSON.stringify(packageIds));
			Form.append('hierarchyIds', JSON.stringify(hierarchyIds));
			Form.append('columns', JSON.stringify(columns));

			return client().post('index.php?option=com_emundus&controller=ranking&task=exportRanking', Form, {
				headers: {
					'Content-Type': 'multipart/form-data',
				},
			});
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getHierarchies() {
		try {
			const response = await client().get('index.php?option=com_emundus&controller=ranking&task=getHierarchies');

			return response.data;
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async deleteHierarchy(hierarchyId) {
		if (hierarchyId > 0) {
			const Form = new FormData();
			Form.append('hierarchyId', hierarchyId);

			const response = await client().post(
				'index.php?option=com_emundus&controller=ranking&task=deleteHierarchy',
				Form,
				{
					headers: {
						'Content-Type': 'multipart/form-data',
					},
				},
			);

			return response.data;
		} else {
			return {
				status: false,
				msg: 'Missing parameter',
			};
		}
	},
	async saveHierarchy(hierarchy) {
		if (hierarchy) {
			let Form = new FormData();
			Form.append('label', hierarchy.label);
			Form.append('profiles', hierarchy.profiles.map((profile) => profile.id).join(','));
			Form.append('editable_status', hierarchy.editable_status.map((status) => status.step).join(','));
			Form.append('visible_status', hierarchy.visible_status.map((status) => status.step).join(','));
			Form.append('visible_hierarchies', hierarchy.visible_hierarchy_ids.map((hierarchy) => hierarchy.id).join(','));
			Form.append('parent_hierarchy', hierarchy.parent_id);
			Form.append('published', hierarchy.published != 0 ? 1 : 0);
			Form.append('form_id', hierarchy.form_id ? hierarchy.form_id : 0);

			if (hierarchy.id === 'tmp') {
				const response = await client().post(
					'/index.php?option=com_emundus&controller=ranking&task=createHierarchy',
					Form,
					{
						headers: {
							'Content-Type': 'multipart/form-data',
						},
					},
				);

				return response.data;
			} else {
				Form.append('id', hierarchy.id);

				const response = await client().post(
					'index.php?option=com_emundus&controller=ranking&task=updateHierarchy',
					Form,
					{
						headers: {
							'Content-Type': 'multipart/form-data',
						},
					},
				);

				return response.data;
			}
		}
	},
	async getAllRankings(hierarchy_id, filters, order) {
		try {
			let params = {
				hierarchy_id: hierarchy_id,
			};

			if (filters.selectedCampaigns.length > 0) {
				params.campaigns_filter = filters.selectedCampaigns.map((campaign) => campaign.id).join(',');
			}

			if (filters.selectedPrograms.length > 0) {
				params.programs_filter = filters.selectedPrograms.map((program) => program.id).join(',');
			}

			if (filters.selectedStatus.length > 0) {
				params.status_filter = filters.selectedStatus.map((status) => status.id).join(',');
			}

			if (filters.fileOrApplicantName.length > 0) {
				params.search_file_or_user = filters.fileOrApplicantName;
			}

			if (filters.rankerName.length > 0) {
				params.search_ranker = filters.rankerName;
			}

			if (order.column) {
				params.order_by_column = order.column;
			}

			if (order.direction) {
				params.order_by_direction = order.direction;
			}

			const response = await client().get('index.php?option=com_emundus&controller=ranking&task=getAllRankings', {
				params: params,
			});

			return response.data;
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async rawUpdateRank(rankRowId, newRank, ccid, hierarchy_id) {
		try {
			const Form = new FormData();
			Form.append('rank_row_id', rankRowId);
			Form.append('new_rank', newRank);
			Form.append('ccid', ccid);
			Form.append('hierarchy_id', hierarchy_id);

			const response = await client().post('index.php?option=com_emundus&controller=ranking&task=rawUpdateRank', Form, {
				headers: {
					'Content-Type': 'multipart/form-data',
				},
			});

			return response.data;
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async rawUpdateRanker(rankRowId, newRanker) {
		try {
			const Form = new FormData();
			Form.append('rank_row_id', rankRowId);
			Form.append('new_ranker', newRanker);

			const response = await client().post(
				'index.php?option=com_emundus&controller=ranking&task=rawUpdateRanker',
				Form,
				{
					headers: {
						'Content-Type': 'multipart/form-data',
					},
				},
			);

			return response.data;
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getAllRankers() {
		try {
			const response = await client().get('index.php?option=com_emundus&controller=ranking&task=getAllRankers');
			return response.data;
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getHierarchyData(hierarchyId) {
		try {
			const response = await client().get(
				'index.php?option=com_emundus&controller=ranking&task=getHierarchyData&hierarchy_id=' + hierarchyId,
			);
			return response.data;
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
};
