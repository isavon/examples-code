SELECT
    cf.feature_id,
    f.name,
    f.type,
    (
        SELECT
            GROUP_CONCAT(CONCAT(du.id, "@@", du.unit) ORDER BY du.sort SEPARATOR "##")
        FROM dimension_unit du
        WHERE du.dimension_id = f.dimension_id
        ORDER BY du.sort
    ) AS dimensions,
    f.dimension_id,
    cf.mandatory,
    cf.important,
    fg.name AS group_name,
    fg.id AS group_id,
    (
        SELECT GROUP_CONCAT(fv.value SEPARATOR "; ")
        FROM category_feature_restricted_value cfrv
        JOIN feature_value fv
            ON fv.id = cfrv.feature_value_id
        WHERE cfrv.feature_id = f.id
            AND cfrv.category_id = :category_id
    ) AS restricted_values
FROM category_feature cf
LEFT JOIN feature f
   ON f.id = cf.feature_id
LEFT JOIN feature_group fg
   ON fg.id = cf.feature_group_id
WHERE cf.category_id = :category_id
ORDER BY cf.sort, cf.feature_id

SELECT
    ccf.id,
    ccf.feature_id,
    f.name system_feature_name,
    fg.name system_feature_group_name,
    ccf.name client_feature_name,
    cfg.name client_feature_group_name,
    ccf.internal_code,
    ccf.internal_code2,
    ccf.client_feature_group_id
FROM client_category_feature ccf
INNER JOIN feature f
    ON f.id = ccf.feature_id
LEFT JOIN category_feature cf
    ON cf.category_id = ccf.category_id
    AND cf.feature_id = ccf.feature_id
LEFT JOIN feature_group fg
    ON fg.id = cf.feature_group_id
LEFT JOIN client_feature_group cfg
    ON cfg.id = ccf.client_feature_group_id
WHERE ccf.client_id = :client_id
  AND ccf.category_id = :category_id
ORDER BY ccf.name

SELECT
    cuf.id,
    cuf.filename,
    cuf.status,
    cuf.info,
    cuf.created_at,
    (SELECT
         CONCAT(
             COUNT(DISTINCT cud.category_internal_code), "##",
             COUNT(DISTINCT cud.client_category_id), "##",
             COUNT(cud.feature_name), "##",
             COUNT(cud.feature_id)
         )
     FROM client_uploaded_data cud
     WHERE cud.client_uploaded_file_id = cuf.id) AS statistic
FROM client_uploaded_file cuf
WHERE cuf.client_id = :client_id
ORDER BY cuf.id DESC

SELECT
    p.sku,
    p.name product_name,
    p.barcode,
    cc.name category_name,
    cc.internal_code,
    (SELECT cud.client_uploaded_file_id FROM client_uploaded_data cud WHERE cud.client_category_id = p.category_id AND cud.client_id = :client_id ORDER BY id DESC LIMIT 1) file_id,
    (SELECT COUNT(id) FROM client_uploaded_data cud WHERE cud.client_uploaded_file_id = file_id) features
FROM product p
LEFT JOIN client_category cc
    ON cc.client_id = p.client_id
    AND cc.category_id = p.category_id
WHERE p.order_id = :order_id

SELECT
    p.category_id,
    c.name category_name,
    (SELECT cud.client_uploaded_file_id FROM client_uploaded_data cud WHERE cud.client_category_id = p.category_id AND cud.client_id = :client_id ORDER BY id DESC LIMIT 1) file_id,
    (SELECT COUNT(id) FROM client_uploaded_data cud WHERE cud.client_uploaded_file_id = file_id) features,
    SUM(IF (p.description_type = "copypaste", 1, 0)) copypaste,
    SUM(IF (p.description_type = "copyright", 1, 0)) copyright
FROM product p
JOIN category c
    ON c.id = p.category_id
WHERE p.order_id = :order_id
GROUP BY p.category_id

